@echo off
echo Generando lista de archivos (Manifest)...
powershell -NoProfile -ExecutionPolicy Bypass -File "GenerateManifest.ps1"
echo.
echo Proceso completado.
pause
