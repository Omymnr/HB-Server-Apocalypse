$baseUrl = "https://raw.githubusercontent.com/Omymnr/HB-Server-Apocalypse/main/"
$exclude = @("files.json", "version.txt", "version.dat", "Helbreath.exe.tmp", "Helbreath.exe.old")
$targetFiles = @("HelbreathLauncher.exe", "Game.exe")
$targetFolders = @("CONTENTS", "SPRITES", "SOUNDS", "MAPDATA", "MUSIC", "FONTS", "RENDER")

# We are running in D:\HB-Server-Apocalypse\
# We need to scan D:\HB-Server-Apocalypse\Helbreath\

$baseDir = $PSScriptRoot
$gameDir = Join-Path $baseDir "Helbreath"

$manifest = @()

# Process specific files
foreach ($file in $targetFiles) {
    $fullPath = Join-Path $gameDir $file
    if (Test-Path $fullPath) {
        $hash = (Get-FileHash $fullPath -Algorithm SHA256).Hash.ToLower()
        $size = (Get-Item $fullPath).Length
        # Path in JSON should be "Helbreath/HelbreathLauncher.exe" to match Repo
        $relPath = "Helbreath/$file" 
        $manifest += @{ Path = $relPath; Hash = $hash; Size = $size }
        Write-Host "Added File: $relPath"
    }
    else {
        Write-Warning "File not found: $fullPath"
    }
}

# Process folders
foreach ($folder in $targetFolders) {
    $fullFolderPath = Join-Path $gameDir $folder
    if (Test-Path $fullFolderPath) {
        $files = Get-ChildItem -Path $fullFolderPath -Recurse -File
        foreach ($f in $files) {
            # Relative path from GameDir
            $subPath = $f.FullName.Substring($gameDir.Length + 1).Replace("\", "/")
            # Prepend Helbreath/
            $repoPath = "Helbreath/$subPath"
            
            if ($exclude -notcontains $f.Name) {
                $hash = (Get-FileHash $f.FullName -Algorithm SHA256).Hash.ToLower()
                $size = $f.Length
                $manifest += @{ Path = $repoPath; Hash = $hash; Size = $size }
            }
        }
    }
}

$json = $manifest | ConvertTo-Json -Depth 5
$json | Set-Content "files.json" -Encoding UTF8

# Increment Version in Root
if (-not (Test-Path "version.txt")) {
    Set-Content "version.txt" "1"
}
else {
    [int]$v = Get-Content "version.txt"
    $v++
    Set-Content "version.txt" $v
}

Write-Host "Manifest generated in Root!"
Write-Host "Version: $(Get-Content version.txt)"
