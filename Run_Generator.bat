@echo off
echo ========================================================
echo       GENERADOR DE ACTUALIZACIONES HELBREATH
echo ========================================================
echo.
echo Generando lista de archivos (Manifest)...
powershell -NoProfile -ExecutionPolicy Bypass -File "GenerateManifest.ps1"
echo.

set /p subir="? Deseas subir los cambios a GitHub para que los usuarios actualicen? (S/N): "
if /i "%subir%" neq "S" goto FIN

echo.
echo Preparando subida a GitHub...
git add .
git commit -m "Game Update via Run_Generator"
echo.
echo Subiendo archivos al servidor...
git push origin main

echo.
echo ========================================================
echo    ACTUALIZACION PUBLICADA CORRECTAMENTE
echo ========================================================
goto END

:FIN
echo.
echo Cambios guardados localmente pero NO subidos.

:END
pause
