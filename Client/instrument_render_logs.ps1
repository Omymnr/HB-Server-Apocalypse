$path = "d:\HB-Server-Apocalypse\Client\Game.cpp"
$txt = Get-Content $path -Raw -Encoding Default

# Pattern to find the render block in UpdateScreen_OnLoading_Progress
# We look for the Unlock call followed by the SpriteBatch calls
$pattern = '(?s)m_DDraw\.m_lpBackB4->Unlock\(NULL\);\s*\}\s*else\s*\{\s*LogDebug\("Failed to Lock BackBuffer"\);\s*\}\s*// m_Renderer->BeginFrame\(0,0,0,1\); // Called at start of frame\s*m_SpriteBatch->Begin\(\);\s*m_SpriteBatch->Draw\(m_TextureManager->GetBackBufferSRV\(\), 0, 0, 800, 600\);\s*m_SpriteBatch->End\(\);\s*m_Renderer->EndFrame\(\);'

$replacement = 'm_DDraw.m_lpBackB4->Unlock(NULL);
    } else {
        LogDebug("Failed to Lock BackBuffer");
    }
    
    LogDebug("Batch Begin");
    m_SpriteBatch->Begin();
    
    LogDebug("Batch Draw");
    m_SpriteBatch->Draw(m_TextureManager->GetBackBufferSRV(), 0, 0, 800, 600);
    
    LogDebug("Batch End");
    m_SpriteBatch->End();
    
    LogDebug("EndFrame Present");
    m_Renderer->EndFrame();
    LogDebug("Frame End Success");'

$newTxt = $txt -replace $pattern, $replacement

if ($newTxt.Length -eq $txt.Length) {
    Write-Output "WARNING: Pattern not found. Regex might be strict."
} else {
    Set-Content $path $newTxt -Encoding Default -NoNewline
    Write-Output "Injected Granular Render Logs."
}
