@echo off
title Protex — Captcha Service (Port 5006)
echo ===========================================
echo   Protex — Service Captcha Python
echo   Port : 5006
echo ===========================================
echo.

REM --- Chercher Python dans les emplacements habituels ---
set PYTHON=
if exist "%LOCALAPPDATA%\Programs\Python\Python311\python.exe" set PYTHON=%LOCALAPPDATA%\Programs\Python\Python311\python.exe
if exist "%LOCALAPPDATA%\Programs\Python\Python312\python.exe" set PYTHON=%LOCALAPPDATA%\Programs\Python\Python312\python.exe
if exist "%LOCALAPPDATA%\Programs\Python\Python310\python.exe" set PYTHON=%LOCALAPPDATA%\Programs\Python\Python310\python.exe
if exist "C:\Python311\python.exe"   set PYTHON=C:\Python311\python.exe
if exist "C:\Python312\python.exe"   set PYTHON=C:\Python312\python.exe
if exist "C:\Python310\python.exe"   set PYTHON=C:\Python310\python.exe

REM --- Fallback : python dans PATH ---
if "%PYTHON%"=="" (
    where python >nul 2>&1
    if %ERRORLEVEL%==0 set PYTHON=python
)

if "%PYTHON%"=="" (
    echo ERREUR : Python non trouve.
    echo Installez Python 3.10+ depuis https://www.python.org/downloads/
    pause
    exit /b 1
)

echo Python trouve : %PYTHON%
echo.

REM --- Verifier/installer les dependances ---
echo [1/2] Installation des dependances pip...
"%PYTHON%" -m pip install Flask Flask-Cors opencv-python numpy Pillow --quiet --no-warn-script-location
echo.

REM --- Demarrer le serveur ---
echo [2/2] Demarrage du service captcha sur http://localhost:5006 ...
echo       (Laisser cette fenetre ouverte pendant l'utilisation de Protex)
echo.
cd /d "%~dp0"
"%PYTHON%" puzzle_engine.py

pause
