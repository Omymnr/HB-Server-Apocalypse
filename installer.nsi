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
!define MUI_WELCOMEPAGE_TITLE "$(WELCOME_TITLE)"
!define MUI_WELCOMEPAGE_TEXT "$(WELCOME_TEXT)"
!define MUI_LICENSEPAGE_TEXT_TOP "$(LICENSE_TITLE)"
!define MUI_DIRECTORYPAGE_TEXT_TOP "$(DIRECTORY_TITLE)"
!define MUI_INSTFILESPAGE_TITLE "$(INSTFILES_TITLE)"
!define MUI_FINISHPAGE_TITLE "$(FINISH_TITLE)"
!define MUI_UNWELCOMEPAGE_TITLE "$(UNINSTALL_TITLE)"

; Pages
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_LICENSE "$(LICENSE_FILE)"
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

; Language Strings
LangString WELCOME_TITLE 1034 "Bienvenido al instalador de Helbreath Apocalypse"
LangString WELCOME_TEXT 1034 "Este instalador le guiará a través de la instalación del cliente de Helbreath Apocalypse.$\r$\n$\r$\n$_CLICK"
LangString LICENSE_TITLE 1034 "Acuerdo de Licencia"
LangString DIRECTORY_TITLE 1034 "Seleccionar Directorio de Instalación"
LangString INSTFILES_TITLE 1034 "Instalando Archivos"
LangString FINISH_TITLE 1034 "Instalación Completada"
LangString SECTION_CLIENT 1034 "Cliente Principal"
LangString COPYING_FILES 1034 "Copiando archivos del cliente..."
LangString UNINSTALL_TITLE 1034 "Desinstalar Helbreath Apocalypse"
LangString LICENSE_FILE 1034 "license.txt"

LangString WELCOME_TITLE 1033 "Welcome to Helbreath Apocalypse Installer"
LangString WELCOME_TEXT 1033 "This installer will guide you through the installation of the Helbreath Apocalypse client.$\r$\n$\r$\n$_CLICK"
LangString LICENSE_TITLE 1033 "License Agreement"
LangString DIRECTORY_TITLE 1033 "Choose Install Location"
LangString INSTFILES_TITLE 1033 "Installing Files"
LangString FINISH_TITLE 1033 "Installation Completed"
LangString SECTION_CLIENT 1033 "Main Client"
LangString COPYING_FILES 1033 "Copying client files..."
LangString UNINSTALL_TITLE 1033 "Uninstall Helbreath Apocalypse"
LangString LICENSE_FILE 1033 "license_en.txt"

Function .onInit
!insertmacro MUI_LANGDLL_DISPLAY
FunctionEnd

; Installer Sections
Section "$(SECTION_CLIENT)" SecClient
    SectionIn RO

    SetOutPath "$INSTDIR"

    ; Copy all files and directories from Helbreath folder
    DetailPrint "$(COPYING_FILES)"
    File /r "D:\HB-Server-Apocalypse\Helbreath\*.*"

    ; Create desktop shortcut
    CreateShortCut "$DESKTOP\Helbreath Apocalypse.lnk" "$INSTDIR\HelbreathLauncher.exe" "" "$INSTDIR\HelbreathLauncher.exe" 0

    ; Store installation folder
    WriteRegStr HKCU "Software\HelbreathApocalypse" "" $INSTDIR

    ; Create uninstaller
    WriteUninstaller "$INSTDIR\Uninstall.exe"

    ; Add uninstall information to Add/Remove Programs
    WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "DisplayName" "Helbreath Apocalypse Client"
    WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "UninstallString" "$INSTDIR\Uninstall.exe"
    WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Uninstall\HelbreathApocalypse" "DisplayIcon" "$INSTDIR\HelbreathLauncher.exe"
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
VIAddVersionKey "LegalCopyright" "© 2025 Helbreath Community"
VIAddVersionKey "FileVersion" "1.0.0.0"
VIAddVersionKey "ProductVersion" "1.0.0.0"
VIAddVersionKey "FileDescription" "Instalador del cliente de Helbreath Apocalypse"