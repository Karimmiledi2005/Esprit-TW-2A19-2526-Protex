@echo off
chcp 65001 > nul
title Protex Face ID Engine
echo [PROTEX] Demarrage du moteur de reconnaissance faciale...
cd /d "c:\xampp\htdocs\assurance\face_api"
python face_engine.py
pause
