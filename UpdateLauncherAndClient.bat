@echo off
rem Update manifest
powershell -ExecutionPolicy Bypass -File "$(Resolve-Path .\GenerateManifest.ps1)"

rem Build client if MSBuild is available
where msbuild >nul 2>&1
if %errorlevel%==0 (
    echo Building client with MSBuild...
    msbuild "Client\Client.vcxproj" /p:Configuration=Release
    if %errorlevel%==0 (
        echo Copying Game.exe to Helbreath folder...
        copy /Y "Client\Release\Game.exe" "Helbreath\Game.exe"
    ) else (
        echo Build failed. Check errors above.
    )
) else (
    echo MSBuild not found in PATH. Please run this script from a Developer Command Prompt or build manually.
)
pause
