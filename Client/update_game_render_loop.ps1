$path = "d:\HB-Server-Apocalypse\Client\Game.cpp"
$txt = Get-Content $path -Raw

# Definition of the new render block
# We use simple string since we are inside a giant function usually
$dx11Block = "
// [DX11 Integration] Hybrid Frame End
if (m_Renderer && m_SpriteBatch && m_TextureManager && m_DDraw.m_pBackB4Addr) {
    m_TextureManager->UpdateBackBuffer(m_DDraw.m_pBackB4Addr, 800, 600);
    // m_Renderer->BeginFrame(0,0,0,1); // Called at start of frame
    m_SpriteBatch->Begin();
    m_SpriteBatch->Draw(m_TextureManager->GetBackBufferSRV(), 0, 0, 800, 600);
    m_SpriteBatch->End();
    m_Renderer->EndFrame();
}
// Legacy Flip disabled
// if (m_DDraw.iFlip() == DDERR_SURFACELOST) RestoreSprites();
"

# Replace standard Flip pattern
# We use regex to handle spaces
$txt = $txt -replace "if\s*\(\s*m_DDraw\.iFlip\(\)\s*==\s*DDERR_SURFACELOST\s*\)\s*RestoreSprites\(\);", $dx11Block

# Replace standalone iFlip calls if any (e.g. m_DDraw.iFlip(); without check)
# Be careful not to double replace if the previous regex matched.
# The previous regex consumes the whole line.
# We might miss "m_DDraw.iFlip();" if it's on a line by itself.
# Let's search for "m_DDraw.iFlip();" specifically.
# But replacing it might duplicate logic if we are not careful about context.
# Most calls in Game.cpp are the if-check pattern.

# Replace ClearBackB4 with BeginFrame
$dx11Begin = "
// [DX11 Integration] Frame Start
if (m_Renderer) m_Renderer->BeginFrame(0.0f, 0.0f, 0.0f, 1.0f);
m_DDraw.ClearBackB4();
"
$txt = $txt -replace "m_DDraw\.ClearBackB4\(\);", $dx11Begin

Set-Content $path $txt -NoNewline
Write-Output "Game.cpp Render Loop Modified"
