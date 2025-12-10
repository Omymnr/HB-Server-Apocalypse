# Helbreath Apocalypse Client Installer
# PowerShell script to install the client

param(
    [string]$InstallPath = "C:\Games\Helbreath"
)

Write-Host "Helbreath Apocalypse Client Installer" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

# Check if path exists, if not create
if (-not (Test-Path $InstallPath)) {
    New-Item -ItemType Directory -Path $InstallPath -Force
}

Write-Host "Instalando en: $InstallPath"

# Copy all files from Helbreath folder
$sourcePath = "D:\HB-Server-Apocalypse\Helbreath"
Write-Host "Copiando archivos..."

Copy-Item -Path "$sourcePath\*" -Destination $InstallPath -Recurse -Force

# Create desktop shortcut
$WshShell = New-Object -comObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut("$([Environment]::GetFolderPath('Desktop'))\Helbreath Apocalypse.lnk")
$Shortcut.TargetPath = "$InstallPath\Game.exe"
$Shortcut.WorkingDirectory = $InstallPath
$Shortcut.IconLocation = "$InstallPath\Game.exe"
$Shortcut.Save()

Write-Host "Instalación completada!" -ForegroundColor Green
Write-Host "Se ha creado un acceso directo en el escritorio."
Read-Host "Presione Enter para salir"