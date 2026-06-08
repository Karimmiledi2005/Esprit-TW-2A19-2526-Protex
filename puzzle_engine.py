import base64
import io
import uuid
import time
import random
import cv2
import numpy as np
from PIL import Image
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app, resources={r"/puzzle/*": {"origins": "*"}})

# Store active sessions: token -> { 'type': str, 'correct': any, 'expires_at': float, 'attempts': int }
sessions = {}
# IP blacklist: ip -> unban_time
blacklist = {}

@app.before_request
def clear_blacklist_on_start():
    # Force clear blacklist for debugging
    blacklist.clear()

def clean_sessions():
    now = time.time()
    expired = [k for k, v in sessions.items() if v['expires_at'] < now]
    for k in expired:
        del sessions[k]

def check_blacklist(ip):
    # Désactivation temporaire de la blacklist pour débloquer le développement
    return False
    if ip in blacklist:
        if time.time() < blacklist[ip]:
            return True
        else:
            del blacklist[ip]
    return False

def add_attempt(ip, token):
    if token in sessions:
        sessions[token]['attempts'] += 1
        if sessions[token]['attempts'] >= 3:
            blacklist[ip] = time.time() + 300 # 5 minutes ban
            del sessions[token]
            return False
    return True

def create_token(puzzle_type, correct_value):
    clean_sessions()
    token = str(uuid.uuid4())
    sessions[token] = {
        'type': puzzle_type,
        'correct': correct_value,
        'expires_at': time.time() + 120, # 2 minutes validity
        'attempts': 0
    }
    return token

def encode_cv2_image(img):
    _, buffer = cv2.imencode('.png', img)
    return base64.b64encode(buffer).decode('utf-8')

@app.route('/puzzle/slider', methods=['GET'])
def slider_puzzle():
    ip = request.remote_addr
    if check_blacklist(ip): return jsonify({"error": "Banned"}), 403
    
    # Generate random background
    bg = np.zeros((150, 300, 3), dtype=np.uint8)
    bg[:] = (random.randint(50, 100), random.randint(50, 100), random.randint(100, 200))
    # Draw some shapes
    for _ in range(5):
        cv2.circle(bg, (random.randint(0, 300), random.randint(0, 150)), random.randint(10, 40), (random.randint(0, 255), random.randint(0, 255), random.randint(0, 255)), -1)
    
    # Cut a piece
    piece_w, piece_h = 40, 40
    correct_x = random.randint(50, 250)
    correct_y = random.randint(20, 110)
    
    piece = bg[correct_y:correct_y+piece_h, correct_x:correct_x+piece_w].copy()
    
    # Draw a hole on background
    bg[correct_y:correct_y+piece_h, correct_x:correct_x+piece_w] = (0, 0, 0)
    
    token = create_token('slider', correct_x)
    return jsonify({
        "image_bg": encode_cv2_image(bg),
        "piece": encode_cv2_image(piece),
        "correct_x": correct_x,
        "y": correct_y,
        "tolerance": 20,
        "token": token
    })

@app.route('/puzzle/intrus', methods=['GET'])
def intrus_puzzle():
    ip = request.remote_addr
    if check_blacklist(ip): return jsonify({"error": "Banned"}), 403
    
    correct_idx = random.randint(0, 8)
    images = []
    
    base_color = (random.randint(50, 200), random.randint(50, 200), random.randint(50, 200))
    intrus_color = ((base_color[0] + 100) % 255, (base_color[1] + 100) % 255, (base_color[2] + 100) % 255)
    
    for i in range(9):
        img = np.zeros((88, 88, 3), dtype=np.uint8)
        color = intrus_color if i == correct_idx else base_color
        cv2.rectangle(img, (10, 10), (78, 78), color, -1)
        images.append(encode_cv2_image(img))
        
    token = create_token('intrus', correct_idx)
    return jsonify({
        "images": images,
        "correct_index": correct_idx,
        "token": token
    })

@app.route('/puzzle/rotation', methods=['GET'])
def rotation_puzzle():
    # Bypass check_blacklist for this route specifically to debug
    # ip = request.remote_addr
    # if check_blacklist(ip): return jsonify({"error": "Banned"}), 403
    
    img = np.zeros((120, 120, 3), dtype=np.uint8)
    img[:] = (200, 200, 200)
    # Draw an arrow pointing UP
    cv2.arrowedLine(img, (60, 100), (60, 20), (0, 0, 255), 10, tipLength=0.3)
    
    angle = random.randint(45, 315)
    
    # Rotate image by 'angle'
    M = cv2.getRotationMatrix2D((60, 60), angle, 1.0)
    img_rot = cv2.warpAffine(img, M, (120, 120))
    
    # The correct angle to rotate it back is 360 - angle
    correct_angle = 360 - angle
    
    token = create_token('rotation', correct_angle)
    return jsonify({
        "image": encode_cv2_image(img_rot),
        "correct_angle": correct_angle,
        "token": token
    })

@app.route('/puzzle/verify', methods=['POST'])
def verify_slider():
    data = request.json
    ip = request.remote_addr
    token = data.get('token')
    x = data.get('x', -1)
    
    if token not in sessions or sessions[token]['type'] != 'slider':
        return jsonify({"success": False, "message": "Token invalide ou expiré"})
        
    if abs(sessions[token]['correct'] - x) <= 20:
        del sessions[token]
        return jsonify({"success": True})
        
    if not add_attempt(ip, token):
        return jsonify({"success": False, "message": "Trop de tentatives, IP bloquée"})
    return jsonify({"success": False})

@app.route('/puzzle/verify_intrus', methods=['POST'])
def verify_intrus():
    data = request.json
    ip = request.remote_addr
    token = data.get('token')
    idx = data.get('index', -1)
    
    if token not in sessions or sessions[token]['type'] != 'intrus':
        return jsonify({"success": False, "message": "Token invalide ou expiré"})
        
    if sessions[token]['correct'] == idx:
        del sessions[token]
        return jsonify({"success": True})
        
    if not add_attempt(ip, token):
        return jsonify({"success": False, "message": "Trop de tentatives, IP bloquée"})
    return jsonify({"success": False})

@app.route('/puzzle/verify_rotation', methods=['POST'])
def verify_rotation():
    data = request.json
    ip = request.remote_addr
    token = data.get('token')
    angle = data.get('angle', -1)
    
    if token not in sessions or sessions[token]['type'] != 'rotation':
        return jsonify({"success": False, "message": "Token invalide ou expiré"})
        
    correct = sessions[token]['correct']
    # allow some tolerance
    diff = abs((correct - angle + 180) % 360 - 180)
    
    if diff <= 20:
        del sessions[token]
        return jsonify({"success": True})
        
    if not add_attempt(ip, token):
        return jsonify({"success": False, "message": "Trop de tentatives, IP bloquée"})
    return jsonify({"success": False})

if __name__ == '__main__':
    app.run(port=5006)
