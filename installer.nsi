; NSIS Installer Script for Helbreath Apocalypse Client
; This script creates an installer for the Helbreath folder contents

!include "MUI2.nsh"

; General Configuration
Name "Helbreath Apocalypse Client"
OutFile "HelbreathApocalypseInstaller.exe"
Unicode True
InstallDir "C:\Games\Helbreath"
InstallDirRegKey HKCU "Software\HelbreathApocalypse" ""
RequestExecutionLevel user

; Icon
!define MUI_ICON "D:\HB-Server-Apocalypse\Client\server_ico.ico"
!define MUI_UNICON "D:\HB-Server-Apocalypse\Client\server_ico.ico"

; Modern UI Configuration
!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "Bienvenido al instalador de Helbreath Apocalypse"
!define MUI_WELCOMEPAGE_TEXT "Este instalador le guiará a través de la instalación del cliente de Helbreath Apocalypse.$\r$\n$\r$\n$_CLICK"

; Pages
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_LICENSE "license.txt" ; You can create a license file
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH

!insertmacro MUI_UNPAGE_WELCOME
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES
!insertmacro MUI_UNPAGE_FINISH

; Languages
!insertmacro MUI_LANGUAGE "Spanish"
!insertmacro MUI_LANGUAGE "English"

; Installer Sections
Section "Cliente Principal" SecClient
    SectionIn RO

    SetOutPath "$INSTDIR"

    ; Copy all files and directories from Helbreath folder
    DetailPrint "Copiando archivos del cliente..."
    File /r "D:\HB-Server-Apocalypse\Helbreath\*.*"

    ; Create desktop shortcut
    CreateShortCut "$DESKTOP\Helbreath Apocalypse.lnk" "$INSTDIR\Game.exe" "" "$INSTDIR\Game.exe" 0

    ; Store installation folder
    WriteRegStr HKCU "Software\HelbreathApocalypse" "" $INSTDIR

    ; Create uninstaller
    WriteUninstaller "$INSTDIR\Uninstall.exe"

    ; Add uninstall information to Add/Remove Programs
    WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "DisplayName" "Helbreath Apocalypse Client"
    WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "UninstallString" "$INSTDIR\Uninstall.exe"
    WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "DisplayIcon" "$INSTDIR\Game.exe"
    WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "Publisher" "Helbreath Community"
    WriteRegDWord HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "NoModify" 1
    WriteRegDWord HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "NoRepair" 1

SectionEnd

; Uninstaller Section
Section "Uninstall"

    Delete "$DESKTOP\Helbreath Apocalypse.lnk"
    Delete "$INSTDIR\Uninstall.exe"

    ; Remove all files and directories
    RMDir /r "$INSTDIR"

    ; Remove registry entries
    DeleteRegKey HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse"
    DeleteRegKey HKCU "Software\HelbreathApocalypse"

SectionEnd

; Version Information
VIProductVersion "1.0.0.0"
VIAddVersionKey "ProductName" "Helbreath Apocalypse Client"
VIAddVersionKey "CompanyName" "Helbreath Community"
VIAddVersionKey "FileVersion" "1.0.0.0"
VIAddVersionKey "ProductVersion" "1.0.0.0"
VIAddVersionKey "FileDescription" "Instalador del cliente de Helbreath Apocalypse"