# Microservices Flask

## Face ID (port 5000)

**Fichier** : `face_api/face_engine.py`
**Technologie** : OpenCV LBPH (Local Binary Patterns Histograms)

### Endpoints

| Route | Method | Description |
|-------|--------|-------------|
| `/register` | POST | Enregistrer un visage (5+ images base64 + user_id) |
| `/verify` | POST | Verifier un visage (image base64 + user_id) |
| `/identify` | POST | Identifier un utilisateur parmi tous les enregistres |
| `/liveness` | POST | Detection de vivacite (5-8 frames, detection de clignement) |

### Seuils

- Verification : 70.0 (plus bas = meilleure correspondance)
- Identification : 65.0

**Lancement** : `python face_api/face_engine.py` ou `Lancer_Tous_Les_Moteurs.bat`

---

## Puzzle CAPTCHA (port 5006)

**Fichier** : `puzzle_engine.py`
**Technologie** : OpenCV + Flask, sessions en memoire avec expiration 2 min

### Endpoints

| Route | Method | Description |
|-------|--------|-------------|
| `/puzzle/slider` | GET | Puzzle glissiere (piece a deplacer) |
| `/puzzle/intrus` | GET | Puzzle intrus (9 images, 1 differente) |
| `/puzzle/rotation` | GET| Puzzle rotation (image a orienter) |
| `/puzzle/verify` | POST | Verifier puzzle glissiere (token + x) |
| `/puzzle/verify_intrus` | POST | Verifier puzzle intrus (token + index) |
| `/puzzle/verify_rotation` | POST | Verifier puzzle rotation (token + angle) |

### Securite

- Tolerance glissiere : 20px
- Tolerance rotation : 20 degres
- Blacklist IP apres 3 echecs (ban 5 min)

**Lancement** : `python puzzle_engine.py` ou `START_CAPTCHA.bat`

---

## OCR (port 5007)

**Fichier** : `ocr_engine.py`
**Technologie** : Tesseract OCR + OpenCV preprocessing

### Endpoints

| Route | Method | Description |
|-------|--------|-------------|
| `/extract_document` | POST | Extraire les donnees d'une piece d'identite (image base64) |

### Donnees extraites

- CIN (8 chiffres)
- date_naissance
- nom
- prenom
- nationalite
- email
- telephone
- adresse

5 passes OCR (Arabe, Francais, Anglais) pour une meilleure reconnaissance.

**Lancement** : `python ocr_engine.py` ou `Lancer_Tous_Les_Moteurs.bat`

---

## AI Contract Generator (standalone)

**Fichier** : `ai_contract/generate_need_contract.py`
**Protocole** : stdin/stdout JSON (pas de serveur persistant)
**Fonction** : Moteur de recommandation de contrat par regles. Prend en entree le budget, profil risque, details vehicule/logement. Retourne la formule la mieux adaptee avec recommandations.

---

## Demarrage rapide

```bash
# Demarrer tous les moteurs
./Lancer_Tous_Les_Moteurs.bat

# Ou individuellement
python face_api/face_engine.py    # Port 5000
python puzzle_engine.py            # Port 5006
python ocr_engine.py               # Port 5007
```
