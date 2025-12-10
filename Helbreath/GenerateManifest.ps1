$baseUrl = "https://raw.githubusercontent.com/Omymnr/HB-Server-Apocalypse/main/Helbreath/"
$exclude = @("files.json", "version.txt", "version.dat", "Helbreath.exe.tmp", "Helbreath.exe.old")
$targetFiles = @("HelbreathLauncher.exe", "Game.exe")
$targetFolders = @("CONTENTS", "SPRITES", "SOUNDS", "MAPDATA", "MUSIC", "FONTS", "RENDER")

$manifest = @()

# Process specific files
foreach ($file in $targetFiles) {
    if (Test-Path $file) {
        $hash = (Get-FileHash $file -Algorithm SHA256).Hash.ToLower()
        $size = (Get-Item $file).Length
        $manifest += @{ Path = $file; Hash = $hash; Size = $size }
        Write-Host "Added File: $file"
    }
}

# Process folders
foreach ($folder in $targetFolders) {
    if (Test-Path $folder) {
        $files = Get-ChildItem -Path $folder -Recurse -File
        foreach ($f in $files) {
            $relPath = $f.FullName.Substring($PWD.Path.Length + 1).Replace("\", "/")
            if ($exclude -notcontains $f.Name) {
                $hash = (Get-FileHash $f.FullName -Algorithm SHA256).Hash.ToLower()
                $size = $f.Length
                $manifest += @{ Path = $relPath; Hash = $hash; Size = $size }
                # Write-Host "Added: $relPath"
            }
        }
    }
}

$json = $manifest | ConvertTo-Json -Depth 5
$json | Set-Content "files.json" -Encoding UTF8

# Increment Version
if (-not (Test-Path "version.txt")) {
    Set-Content "version.txt" "1"
}
else {
    [int]$v = Get-Content "version.txt"
    $v++
    Set-Content "version.txt" $v
}

Write-Host "Manifest generated!"
Write-Host "Version: $(Get-Content version.txt)"
Write-Host "Upload 'files.json' and 'version.txt' to GitHub 'Helbreath/' folder."
