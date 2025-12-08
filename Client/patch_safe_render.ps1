$path = "d:\HB-Server-Apocalypse\Client\Game.cpp"
$txt = Get-Content $path -Raw -Encoding Default

# Define the unsafe block pattern (allowing for whitespace variations)
# Match: if (checks...) { LogDebug... UpdateBackBuffer...
$pattern = 'if \(m_Renderer && m_SpriteBatch && m_TextureManager && m_DDraw\.m_pBackB4Addr\) \{\s+LogDebug\("Frame Start Upload"\);\s+m_TextureManager->UpdateBackBuffer\(m_DDraw\.m_pBackB4Addr, m_DDraw\.m_sBackB4Pitch, 800, 600\);'

# Define the Replacement Block
$replacement = 'if (m_Renderer && m_SpriteBatch && m_TextureManager) {
    DDSURFACEDESC2 ddsd;
    ZeroMemory(&ddsd, sizeof(ddsd));
    ddsd.dwSize = sizeof(ddsd);
    // Lock BackBuffer safely
    if (m_DDraw.m_lpBackB4->Lock(NULL, &ddsd, DDLOCK_WAIT, NULL) == DD_OK) {
        // LogDebug("Locked BackBuffer");
        // Update with valid pointer and pitch (pixels)
        m_TextureManager->UpdateBackBuffer(ddsd.lpSurface, ddsd.lPitch / 2, 800, 600);
        m_DDraw.m_lpBackB4->Unlock(NULL);
    } else {
        LogDebug("Failed to Lock BackBuffer");
    }'

# Perform Replacement
$newTxt = $txt -replace $pattern, $replacement

# Verify if changed
if ($newTxt.Length -eq $txt.Length) {
    Write-Output "WARNING: No changes made. Pattern might not match."
} else {
    Set-Content $path $newTxt -Encoding Default -NoNewline
    Write-Output "Applied Safe Lock/Unlock to Game.cpp"
}
