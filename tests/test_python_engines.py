import sys
import os
import unittest
from unittest.mock import patch, MagicMock

# Add root directory and face_api to sys.path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.append(os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'face_api'))

from puzzle_engine import app as puzzle_app
from ocr_engine import app as ocr_app
from face_engine import app as face_app

class TestPuzzleEngine(unittest.TestCase):
    def setUp(self):
        self.app = puzzle_app.test_client()
        self.app.testing = True

    def test_slider_puzzle(self):
        response = self.app.get('/puzzle/slider')
        self.assertEqual(response.status_code, 200)
        data = response.get_json()
        self.assertIn('image_bg', data)
        self.assertIn('piece', data)
        self.assertIn('token', data)
        self.assertIn('correct_x', data)

    def test_intrus_puzzle(self):
        response = self.app.get('/puzzle/intrus')
        self.assertEqual(response.status_code, 200)
        data = response.get_json()
        self.assertIn('images', data)
        self.assertEqual(len(data['images']), 9)
        self.assertIn('token', data)

    def test_rotation_puzzle(self):
        response = self.app.get('/puzzle/rotation')
        self.assertEqual(response.status_code, 200)
        data = response.get_json()
        self.assertIn('image', data)
        self.assertIn('token', data)

    def test_verify_slider_invalid_token(self):
        response = self.app.post('/puzzle/verify', json={'token': 'invalid', 'x': 50})
        data = response.get_json()
        self.assertFalse(data['success'])
        self.assertEqual(data['message'], "Token invalide ou expiré")


class TestOCREngine(unittest.TestCase):
    def setUp(self):
        self.app = ocr_app.test_client()
        self.app.testing = True

    @patch('ocr_engine.pytesseract.image_to_string')
    @patch('ocr_engine.cv2.imdecode')
    def test_extract_document_missing_image(self, mock_imdecode, mock_ocr):
        response = self.app.post('/extract_document', json={})
        self.assertEqual(response.status_code, 200)
        data = response.get_json()
        self.assertFalse(data['success'])
        self.assertEqual(data['message'], "Aucune image envoyée")


class TestFaceEngine(unittest.TestCase):
    def setUp(self):
        self.app = face_app.test_client()
        self.app.testing = True

    def test_register_missing_data(self):
        response = self.app.post('/register', json={})
        self.assertEqual(response.status_code, 400)
        data = response.get_json()
        self.assertFalse(data['success'])
        
    def test_verify_missing_data(self):
        response = self.app.post('/verify', json={})
        self.assertEqual(response.status_code, 400)

if __name__ == '__main__':
    unittest.main()
