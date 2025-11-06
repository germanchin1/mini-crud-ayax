@echo off
REM ============================================
REM 🚀 Script para iniciar Mini CRUD PHP con Docker
REM ============================================

REM Cambia esta ruta a la carpeta de tu proyecto
set PROYECTO=C:\ruta\a\mini_crud_ajax

echo.
echo ============================================
echo Iniciando contenedor Docker para Mini CRUD PHP
echo ============================================
echo.

cd /d "%PROYECTO%"

REM Levanta el contenedor (si no existe, lo crea)
docker compose up --build -d

if %errorlevel% neq 0 (
    echo.
    echo ❌ Error al iniciar el contenedor.
    pause
    exit /b
)

echo.
echo ✅ Contenedor iniciado correctamente.
echo 🌐 Abre tu navegador en: http://localhost:8080
echo.

pause
exit
