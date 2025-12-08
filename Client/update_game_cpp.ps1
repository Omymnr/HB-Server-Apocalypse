$path = "d:\HB-Server-Apocalypse\Client\Game.cpp"
$txt = Get-Content $path -Raw
# Constructor injection
$ctorSearch = "CGame::CGame() {"
$ctorReplace = "CGame::CGame() {`r`n    m_Renderer = nullptr;`r`n    m_SpriteBatch = nullptr;`r`n    m_TextureManager = nullptr;"
if ($txt -notmatch "m_Renderer = nullptr") {
    $txt = $txt.Replace($ctorSearch, $ctorReplace)
}

# bInit injection
$initSearch = "if (m_DDraw.bInit(m_hWnd) == false)"
$initReplace = "m_Renderer = new DX11Renderer();`r`n    if (!m_Renderer->Initialize(m_hWnd, 800, 600, true)) return false;`r`n    m_SpriteBatch = new SpriteBatcher(m_Renderer);`r`n    m_SpriteBatch->Initialize();`r`n    m_TextureManager = new TextureManager(m_Renderer);`r`n`r`n    if (m_DDraw.bInit(m_hWnd) == false)"
if ($txt -notmatch "m_Renderer = new DX11Renderer") {
    $txt = $txt.Replace($initSearch, $initReplace)
}

# Add include if missing (checking a known include)
if ($txt -notmatch "Renderer/DX11Renderer.h") {
    # Game.cpp likely includes Game.h, so we might not need this if Game.h has it.
    # But let's verify Game.h is included.
    # We already updated Game.h. So we shouldn't need to add includes here.
}

Set-Content $path $txt -NoNewline
Write-Output "Game.cpp Modified"
