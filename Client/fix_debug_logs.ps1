$gamePath = "d:\HB-Server-Apocalypse\Client\Game.cpp"
$tmPath = "d:\HB-Server-Apocalypse\Client\Renderer\TextureManager.cpp"

# Game.cpp: Uncomment // LogDebug("Locked BackBuffer");
# Regex: //\s*LogDebug\("Locked BackBuffer"\);
$gameTxt = Get-Content $gamePath -Raw -Encoding Default
$gameTxt = $gameTxt -replace '//\s*LogDebug\("Locked BackBuffer"\);', 'LogDebug("Locked BackBuffer");'
Set-Content $gamePath $gameTxt -Encoding Default -NoNewline

# TextureManager.cpp: Uncomment // LogDebug("UpdateBackBuffer...
# Regex: //\s*LogDebug\("UpdateBackBuffer
$tmTxt = Get-Content $tmPath -Raw -Encoding Default
$tmTxt = $tmTxt -replace '//\s*LogDebug\("UpdateBackBuffer', 'LogDebug("UpdateBackBuffer'
Set-Content $tmPath $tmTxt -Encoding Default -NoNewline

Write-Output "Logs Uncommented Successfully"
