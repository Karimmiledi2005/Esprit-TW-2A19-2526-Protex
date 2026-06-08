import os
from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import numpy as np
import pytesseract
import re
import base64
from datetime import datetime

app = Flask(__name__)
CORS(app)

pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
os.environ['TESSDATA_PREFIX'] = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'tessdata')

@app.route('/extract_document', methods=['POST'])
def extract_document():
    data = request.json
    if not data or 'image' not in data:
        return jsonify({'success': False, 'message': "Aucune image envoyée"})
    
    try:
        img_data = base64.b64decode(data['image'])
        np_arr = np.frombuffer(img_data, np.uint8)
        img = cv2.imdecode(np_arr, cv2.IMREAD_COLOR)

        def preprocess_variants(source_img):
            resized = cv2.resize(source_img, None, fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC)
            gray_img = cv2.cvtColor(resized, cv2.COLOR_BGR2GRAY)
            gray_img = cv2.fastNlMeansDenoising(gray_img, h=4)
            gray_img = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8)).apply(gray_img)
            blur_img = cv2.GaussianBlur(gray_img, (3, 3), 0)
            _, thresh_img = cv2.threshold(blur_img, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
            inv_img = cv2.bitwise_not(thresh_img)
            return {
                'resized': resized,
                'gray': gray_img,
                'thresh': thresh_img,
                'inv': inv_img,
                'right_half': resized[:, resized.shape[1] // 3:]
            }

        def ocr_pass(image, lang, config):
            return pytesseract.image_to_string(image, lang=lang, config=config)

        def clean_text(value):
            return re.sub(r'\s+', ' ', value or '').strip()

        def normalize_name(value):
            value = clean_text(value)
            value = re.sub(r'^(?:NOM|NAME|PRENOM|PRÉNOM)\s*[:.]?\s*', '', value, flags=re.I)
            value = re.sub(r'[^A-Za-zÀ-ÿ\u0600-\u06FF\s\-\']+', ' ', value)
            value = re.sub(r'\s+', ' ', value).strip()
            return value

        # Preprocessing - optimized for text recognition (less aggressive)
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Denoise - gentle denoising only
        gray = cv2.fastNlMeansDenoising(gray, h=5)
        
        # Apply CLAHE for contrast enhancement
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
        gray = clahe.apply(gray)
        
        # Bilateral filter - preserves edges while smoothing
        gray = cv2.bilateralFilter(gray, 9, 75, 75)
        
        # Use Otsu's thresholding (automatic threshold selection)
        _, gray = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        
        # Skip morphological operations - they can destroy small text details
        
        custom_config = r'--oem 3'
        variants = preprocess_variants(img)

        # OCR with multiple passes to improve weak student-card text
        text = ocr_pass(gray, 'ara+fra+eng', custom_config)
        text_alt = ocr_pass(gray, 'fra+ara+eng', custom_config)
        text_zoom = ocr_pass(variants['thresh'], 'fra+eng', custom_config)
        text_inv = ocr_pass(variants['inv'], 'fra+eng', custom_config)
        text_right = ocr_pass(variants['right_half'], 'fra+eng', custom_config)
        
        print("--- RAW OCR TEXT (Arabic Priority) ---")
        print(text)
        print("--- Alternative OCR TEXT (French Priority) ---")
        print(text_alt)
        print("--- Zoom OCR TEXT ---")
        print(text_zoom)
        print("--- Inverted OCR TEXT ---")
        print(text_inv)
        print("--- Right Half OCR TEXT ---")
        print(text_right)
        print("--------------------")

        all_text = "\n".join([text, text_alt, text_zoom, text_inv, text_right])
        
        # Extract any text - be more lenient and look for patterns
        # For Arabic documents, extract 8-digit numbers (CIN), dates, and any word sequences
        
        # CIN: 8 consecutive digits (most reliable for ID cards)
        cin_match = re.search(r'\b(\d{8})\b', all_text)
        if not cin_match:
            cin_match = re.search(r'\b(\d{8})\b', text_right)
        student_id_match = re.search(r'(?i)(?:N°\s*étudiant|N°\s*etudiant|NUMERO|IDENTIFIANT|ID)[\s:.\-]*([A-Z0-9]{6,20})', all_text)
        
        # Date: dd/mm/yyyy or dd.mm.yyyy format
        date_match = re.search(r'\b(\d{2}[/\.]\d{2}[/\.]\d{4})\b', all_text)
        if not date_match:
            date_match = re.search(r'\b(\d{2}[/\.]\d{2}[/\.]\d{4})\b', text_right)
        
        # Regex for French/English labels
        nom_match = re.search(r'(?im)^\s*(?:NOM|Nom|NAME)\s*[:.]?\s*([A-ZÀ-ÿ\u0600-\u06FF\s\-\']{2,60})$', all_text)
        prenom_match = re.search(r'(?im)^\s*(?:PRENOM|PRÉNOM|Prenom|FIRSTNAME)\s*[:.]?\s*([A-ZÀ-ÿ\u0600-\u06FF\s\-\']{2,60})$', all_text)
        nat_match = re.search(r'(?i)(?:NATIONALITE|NATIONALITÉ)[\s:.]*([A-Za-z]+)', all_text)
        email_match = re.search(r'(?i)\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})\b', all_text)
        telephone_match = re.search(
            r'(?i)(?:TEL|TÉL|TELEPHONE|TÉLÉPHONE|PHONE|MOBILE)[\s:.\-]*((?:\+216\s?)?[2-9](?:[\s\-]?\d){7})',
            all_text
        )
        adresse_match = re.search(
            r'(?i)(?:ADRESSE|ADDRESS|ADDR)[\s:.]*([A-Za-zÀ-ÿ0-9\s,\-\/]+)',
            all_text
        )
        
        # If still no name found, try to extract the first meaningful line of text (could be Arabic)
        nom = normalize_name(nom_match.group(1)) if nom_match else ""
        prenom = normalize_name(prenom_match.group(1)) if prenom_match else ""
        
        # If French extraction failed, try to get words from Arabic text
        if not nom and not prenom:
            lines = [clean_text(line) for line in all_text.split('\n') if clean_text(line)]
            if len(lines) > 1:
                nom = lines[0][:30] if lines[0] else ""
                prenom = lines[1][:30] if len(lines) > 1 else ""
        
        date_naissance = date_match.group(1).replace('.', '/') if date_match else ""
        cin_number = cin_match.group(1) if cin_match else ""
        student_id = student_id_match.group(1) if student_id_match else ""
        if not cin_number and student_id:
            cin_number = student_id
        nationalite = nat_match.group(1).strip() if nat_match else ""
        email = email_match.group(1).strip() if email_match else ""
        telephone = telephone_match.group(1).strip() if telephone_match else ""
        adresse = adresse_match.group(1).strip() if adresse_match else ""
        telephone = re.sub(r'\s+', ' ', telephone)
        adresse = re.sub(r'\s+', ' ', adresse)
        
        # More lenient confidence calculation - prioritize CIN and date over names
        # For Arabic documents, CIN and date are the most reliable fields
        extracted = sum([bool(cin_number), bool(date_naissance)])
        confiance = extracted / 2  # At least need CIN and date
        
        with open('ocr_log.txt', 'a') as f:
            f.write(
                f"[{datetime.now()}] Confiance: {confiance:.2%} | CIN={cin_number} | Date={date_naissance} | "
                f"Nom={nom} | Prenom={prenom} | Email={email} | Tel={telephone} | Adresse={adresse}\n"
            )
        
        # Accept if we have CIN OR date (more lenient)
        # ID cards are most important for these 2 fields
        if cin_number or date_naissance:
            # Prepare response data
            response_data = {
                'nom': nom if nom else "Non extrait",  # May be in Arabic
                'prenom': prenom if prenom else "Non extrait",  # May be in Arabic
                'date_naissance': date_naissance if date_naissance else "",
                'cin_number': cin_number if cin_number else "",
                'nationalite': nationalite if nationalite else "Tunisienne",
                'email': email if email else "",
                'telephone': telephone if telephone else "",
                'adresse': adresse if adresse else "",
                'confiance': confiance
            }
            
            # Log what was extracted
            extracted_fields = []
            if cin_number:
                extracted_fields.append("CIN")
            if student_id and student_id != cin_number:
                extracted_fields.append("N° étudiant")
            if date_naissance:
                extracted_fields.append("Date")
            if nom and nom != "Non extrait":
                extracted_fields.append("Nom")
            if prenom and prenom != "Non extrait":
                extracted_fields.append("Prénom")
            if email:
                extracted_fields.append("Email")
            if telephone:
                extracted_fields.append("Téléphone")
            if adresse:
                extracted_fields.append("Adresse")
            
            print(f"✅ Extraction réussie: {', '.join(extracted_fields)}")
            
            return jsonify({
                'success': True,
                'data': response_data
            })
        else:
            # No critical fields found - request better photo
            error_msg = (
                "Impossible d'extraire les informations du document. "
                "Conseils:\n"
                "1. Assurez-vous que le CIN/Passeport est bien visible et lisible\n"
                "2. Utilisez une bonne luminosité (lumière naturelle de préférence)\n"
                "3. Tenez l'appareil perpendiculairement au document\n"
                "4. Évitez les reflets et les ombres\n"
                "5. Prenez une photo en haute résolution (800x600 minimum)"
            )
            print(f"❌ Extraction échouée - pas de CIN ni date détectés")
            return jsonify({'success': False, 'message': error_msg})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})

if __name__ == '__main__':
    app.run(port=5007)
