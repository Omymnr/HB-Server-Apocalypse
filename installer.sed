[Version]
Class=IEXPRESS
SEDVersion=3
[Options]
PackagePurpose=InstallApp
ShowInstallProgramWindow=1
HideExtractAnimation=0
UseLongFileName=1
InsideCompressed=0
CAB_FixedSize=0
CAB_ResvCodeSigning=0
RebootMode=N
InstallPrompt=%InstallPrompt%
DisplayLicense=%DisplayLicense%
FinishMessage=%FinishMessage%
TargetName=%TargetName%
FriendlyName=%FriendlyName%
AppLaunched=%AppLaunched%
PostInstallCmd=%PostInstallCmd%
AdminQuietInstCmd=%AdminQuietInstCmd%
UserQuietInstCmd=%UserQuietInstCmd%
SourceFiles=SourceFiles
[Strings]
InstallPrompt=¿Desea instalar Helbreath Apocalypse Client?
DisplayLicense=D:\HB-Server-Apocalypse\license.txt
FinishMessage=Instalación completada. Puede ejecutar Game.exe desde el directorio de instalación.
TargetName=D:\HB-Server-Apocalypse\HelbreathApocalypseInstaller.exe
FriendlyName=Helbreath Apocalypse Client Installer
AppLaunched=cmd /c echo Instalación completada. Presione Enter para salir. && pause
PostInstallCmd=<None>
AdminQuietInstCmd=
UserQuietInstCmd=
FILE0="Game.exe"
FILE1="HelbreathLauncher.exe"
FILE2="search.dll"
[SourceFiles]
SourceFiles0=D:\HB-Server-Apocalypse\Helbreath\
[SourceFiles0]
%FILE0%=
%FILE1%=
%FILE2%=
CONTENTS\=CONTENTS\
FONTS\=FONTS\
MAPDATA\=MAPDATA\
MUSIC\=MUSIC\
Renderer\=Renderer\
SOUNDS\=SOUNDS\
SPRITES\=SPRITES\