@echo off
title PROTEX AI Engines
echo ===================================================
echo        Lancement des Moteurs IA Protex
echo ===================================================

echo [1/3] Demarrage du Face ID Engine (Port 5000)...
start "Face ID" cmd /k "cd /d face_api && python face_engine.py"

echo [2/3] Demarrage du Puzzle Engine (Port 5006)...
start "Puzzle" cmd /k "python puzzle_engine.py"

echo [3/3] Demarrage de l'OCR Engine (Port 5007)...
start "OCR" cmd /k "python ocr_engine.py"

echo.
echo Les moteurs tournent en arriere-plan.
echo.
pause
