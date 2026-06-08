from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import numpy as np
import base64
import os

app = Flask(__name__)
CORS(app)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODELS_DIR = os.path.join(BASE_DIR, 'models')
if not os.path.exists(MODELS_DIR):
    os.makedirs(MODELS_DIR)

# Load Haar cascade for face detection
CASCADE_PATH = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
face_cascade = cv2.CascadeClassifier(CASCADE_PATH)

print(f"Moteur Face ID lancé. Dossier modèles: {MODELS_DIR}")

def base64_to_cv2(base64_string):
    if ',' in base64_string:
        base64_string = base64_string.split(',')[1]
    img_data = base64.b64decode(base64_string)
    nparr = np.frombuffer(img_data, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    return img

@app.route('/register', methods=['POST'])
def register():
    data = request.json
    if 'images' not in data or 'user_id' not in data:
        return jsonify({'success': False, 'message': 'Missing data (need images array and user_id)'}), 400
    
    user_id = str(data['user_id'])
    base64_images = data['images']
    
    if len(base64_images) < 5:
        return jsonify({'success': False, 'message': 'Need at least 5 images for training'}), 400

    faces = []
    labels = []
    
    for b64 in base64_images:
        try:
            img = base64_to_cv2(b64)
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            # Detect face
            detected = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(50, 50))
            if len(detected) > 0:
                (x, y, w, h) = detected[0]
                face_roi = gray[y:y+h, x:x+w]
                face_roi = cv2.resize(face_roi, (200, 200))
                # Preprocessing
                face_roi = cv2.bilateralFilter(face_roi, 9, 75, 75)
                face_roi = cv2.equalizeHist(face_roi)
                
                faces.append(face_roi)
                labels.append(1) # LBPH needs labels, we use 1 for the user
            else:
                # Try flipped for registration too
                flipped = cv2.flip(gray, 1)
                detected = face_cascade.detectMultiScale(flipped, scaleFactor=1.1, minNeighbors=5, minSize=(50, 50))
                if len(detected) > 0:
                    (x, y, w, h) = detected[0]
                    face_roi = flipped[y:y+h, x:x+w]
                    face_roi = cv2.resize(face_roi, (200, 200))
                    face_roi = cv2.bilateralFilter(face_roi, 9, 75, 75)
                    face_roi = cv2.equalizeHist(face_roi)
                    faces.append(face_roi)
                    labels.append(1)
            # break # Take only the first face per image
        except Exception as e:
            print("Error processing an image:", e)
            continue
            
    if len(faces) < 3:
        return jsonify({'success': False, 'message': 'Could not detect enough clear faces in the provided images. Please ensure your face is well lit and visible.'}), 400
        
    try:
        # Train LBPH Recognizer
        recognizer = cv2.face.LBPHFaceRecognizer_create()
        recognizer.train(faces, np.array(labels))
        
        # Save model
        model_path = os.path.join(MODELS_DIR, f'user_{user_id}.yml')
        recognizer.write(model_path)
        
        return jsonify({'success': True, 'message': 'Face ID model trained successfully', 'faces_used': len(faces)})
    except Exception as e:
        return jsonify({'success': False, 'message': 'Internal error: ' + str(e)}), 500

@app.route('/verify', methods=['POST'])
def verify():
    data = request.json
    if 'image' not in data or 'user_id' not in data:
        return jsonify({'success': False, 'message': 'Missing data'}), 400
        
    user_id = str(data['user_id'])
    model_path = os.path.join(MODELS_DIR, f'user_{user_id}.yml')
    
    if not os.path.exists(model_path):
        return jsonify({'success': False, 'message': 'Face ID not configured for this user'}), 404
        
    try:
        img = base64_to_cv2(data['image'])
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Detect face
        detected = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(50, 50))
        if len(detected) == 0:
            # Try once with flipped image just in case
            flipped = cv2.flip(gray, 1)
            detected = face_cascade.detectMultiScale(flipped, scaleFactor=1.1, minNeighbors=5, minSize=(50, 50))
            if len(detected) == 0:
                return jsonify({'success': False, 'message': 'No face detected'}), 400
            gray = flipped

        (x, y, w, h) = detected[0]
        face_roi = gray[y:y+h, x:x+w]
        face_roi = cv2.resize(face_roi, (200, 200))
        
        # Preprocessing: Noise reduction + Histogram equalization
        face_roi = cv2.bilateralFilter(face_roi, 9, 75, 75)
        face_roi = cv2.equalizeHist(face_roi)
        
        # Load recognizer
        recognizer = cv2.face.LBPHFaceRecognizer_create()
        recognizer.read(model_path)
        
        # Predict
        label, confidence = recognizer.predict(face_roi)
        
        # Try with flipped face_roi as well
        face_roi_flipped = cv2.flip(face_roi, 1)
        label_f, confidence_f = recognizer.predict(face_roi_flipped)
        
        final_confidence = min(confidence, confidence_f)
        is_flipped = confidence_f < confidence
            
        # Confidence in LBPH is distance. Lower is better.
        threshold = 70.0 # Strict threshold for high security
        is_match = final_confidence < threshold
        
        print(f"[VERIFY] User {user_id}: Confiance={final_confidence:.2f} (Seuil={threshold}), Match={is_match}, Miroir={is_flipped}")
        
        return jsonify({
            'success': True, 
            'match': bool(is_match),
            'confidence': float(final_confidence),
            'threshold': threshold,
            'message': 'Match found' if is_match else 'User not recognized'
        })
    except Exception as e:
        return jsonify({'success': False, 'message': 'Internal error: ' + str(e)}), 500

@app.route('/identify', methods=['POST'])
def identify():
    data = request.json
    if 'image' not in data:
        return jsonify({'success': False, 'message': 'Missing image data'}), 400
        
    try:
        img = base64_to_cv2(data['image'])
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        detected = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(50, 50))
        if len(detected) == 0:
            flipped = cv2.flip(gray, 1)
            detected = face_cascade.detectMultiScale(flipped, scaleFactor=1.1, minNeighbors=5, minSize=(50, 50))
            if len(detected) == 0:
                return jsonify({'success': False, 'message': 'No face detected'}), 400
            gray = flipped
            
        (x, y, w, h) = detected[0]
        face_roi = cv2.resize(gray[y:y+h, x:x+w], (200, 200))
        face_roi = cv2.bilateralFilter(face_roi, 9, 75, 75)
        face_roi = cv2.equalizeHist(face_roi)
        
        face_roi_flipped = cv2.flip(face_roi, 1)
        
        best_match = None
        min_confidence = 200.0 
        threshold = 65.0 # Very strict for global identification
        
        model_files = [f for f in os.listdir(MODELS_DIR) if f.endswith('.yml')]
        if not model_files:
            return jsonify({'success': False, 'message': 'No trained models found'}), 404

        recognizer = cv2.face.LBPHFaceRecognizer_create()
        for f in model_files:
            try:
                recognizer.read(os.path.join(MODELS_DIR, f))
                # Try normal
                label, confidence = recognizer.predict(face_roi)
                # Try flipped
                label_f, confidence_f = recognizer.predict(face_roi_flipped)
                
                final_conf = min(confidence, confidence_f)
                
                if final_conf < min_confidence:
                    min_confidence = final_conf
                    user_id = f.replace('user_', '').replace('.yml', '')
                    best_match = user_id
            except:
                continue
                
        if best_match and min_confidence < threshold:
            print(f"[IDENTIFY] Match trouvé: User {best_match}, Confiance={min_confidence:.2f}")
            return jsonify({
                'success': True,
                'match': True,
                'user_id': best_match,
                'confidence': float(min_confidence)
            })
        else:
            print(f"[IDENTIFY] Aucun match (Meilleure confiance: {min_confidence:.2f}, Seuil: {threshold})")
            return jsonify({
                'success': False, 
                'match': False, 
                'message': 'User not recognized',
                'confidence': float(min_confidence)
            })
            
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/liveness', methods=['POST'])
def liveness():
    """
    Détection de vivacité par analyse de clignement d'yeux.
    Reçoit 5 à 8 frames (images base64) et vérifie qu'au moins un
    cycle de clignement (ouvert->fermé->ouvert) est détecté.
    """
    data = request.json
    if 'images' not in data or not isinstance(data['images'], list) or len(data['images']) < 5:
        return jsonify({'success': False, 'message': 'Need at least 5 frames'}), 400

    eye_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_eye.xml')
    frames = data['images']
    eye_states = []  # True = ouvert, False = fermé

    for b64 in frames:
        try:
            img = base64_to_cv2(b64)
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            face_rects = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(50, 50))
            if len(face_rects) == 0:
                continue
            (x, y, w, h) = face_rects[0]
            face_roi = gray[y:y+h, x:x+w]
            eyes = eye_cascade.detectMultiScale(face_roi, scaleFactor=1.1, minNeighbors=5, minSize=(15, 15))
            eye_states.append(len(eyes) >= 2)
        except Exception as e:
            print("Liveness frame error:", e)
            continue

    if len(eye_states) < 3:
        return jsonify({'success': False, 'alive': False, 'message': 'Pas assez de visages détectés'}), 400

    # Détecter au moins une transition ouvert->fermé->ouvert
    blinks = 0
    for i in range(1, len(eye_states) - 1):
        if eye_states[i-1] and not eye_states[i] and eye_states[i+1]:
            blinks += 1

    alive = blinks >= 1
    print(f"[LIVENESS] Clignements détectés: {blinks}, Vivant: {alive}")
    return jsonify({
        'success': True,
        'alive': alive,
        'blinks': blinks,
        'message': 'Vivacité confirmée' if alive else 'Aucun mouvement oculaire détecté — possible tentative de spoofing'
    })

if __name__ == '__main__':
    print("Starting Pure AI Face Recognition Engine (OpenCV LBPH) on port 5000...")
    app.run(host='0.0.0.0', port=5000, debug=False)
