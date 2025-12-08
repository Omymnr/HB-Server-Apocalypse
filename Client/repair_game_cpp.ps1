$path = "d:\HB-Server-Apocalypse\Client\Game.cpp"
# Use UTF8 or Default? The file usually is ANSI. 'Get-Content' might detect.
# We will use 'Default' to be safe for legacy files.
$txt = Get-Content $path -Raw -Encoding Default

# 1. Fix Double Brace
# Pattern: Note that line endings might vary. We match loosely.
# We look for the comment we inserted: // LogDebug("Loading Progress: %d", m_cLoading);
# followed by {
$txt = $txt -replace '// LogDebug\("Loading Progress: %d", m_cLoading\);\s*\{', '// LogDebug("Loading Progress: %d", m_cLoading);'

# 2. Update Pitch Call
# Pattern: UpdateBackBuffer(m_DDraw.m_pBackB4Addr, 800, 600)
$txt = $txt -replace 'UpdateBackBuffer\(m_DDraw.m_pBackB4Addr, 800, 600\)', 'UpdateBackBuffer(m_DDraw.m_pBackB4Addr, m_DDraw.m_sBackB4Pitch, 800, 600)'

Set-Content $path $txt -Encoding Default -NoNewline
Write-Output "Game.cpp Repaired"
