$path = "d:\HB-Server-Apocalypse\Client\Game.cpp"
$txt = Get-Content $path -Raw

# Add Log Function at the top
if ($txt -notmatch "void LogDebug") {
    $logFunc = "
#include <fstream>
void LogDebug(const char* fmt, ...) {
    static std::ofstream logFile(""dx11_debug.txt"", std::ios::app);
    char buffer[1024];
    va_list args;
    va_start(args, fmt);
    vsnprintf(buffer, 1024, fmt, args);
    va_end(args);
    logFile << buffer << std::endl;
}
"
    # Insert after includes (assuming include <windows.h> or similar exists)
    $txt = $txt -replace '#include "Game.h"', "#include ""Game.h""`r`n$logFunc"
}

# Log in Constructor
$txt = $txt -replace "CGame::CGame\(\) \{", "CGame::CGame() {`r`n    LogDebug(""CGame::CGame Constructor Invoked"");"

# Log in bInit
$txt = $txt -replace "if \(!m_Renderer->Initialize\(m_hWnd, 800, 600, true\)\) return false;", "if (!m_Renderer->Initialize(m_hWnd, 800, 600, true)) { LogDebug(""Renderer Init Failed""); return false; } else { LogDebug(""Renderer Init Success""); }"

# Log in UpdateScreen_OnLoading_Progress
$txt = $txt -replace "void CGame::UpdateScreen_OnLoading_Progress\(\)", "void CGame::UpdateScreen_OnLoading_Progress()`r`n{`r`n    // LogDebug(""Loading Progress: %d"", m_cLoading);"

# Log in Render Loop (The Block we added)
# Using unique string to find it
if ($txt -match "// \[DX11 Integration\] Hybrid Frame End") {
    # Be careful with replace on big blocks. Let's append log calls.
    # Pattern: m_TextureManager->UpdateBackBuffer
    $txt = $txt -replace "m_TextureManager->UpdateBackBuffer\(", "LogDebug(""Frame Start Upload"");`r`n    m_TextureManager->UpdateBackBuffer("
    
    # Pattern: m_Renderer->EndFrame();
    $txt = $txt -replace "m_Renderer->EndFrame\(\);", "m_Renderer->EndFrame();`r`n    // LogDebug(""Frame End Present"");"
}

Set-Content $path $txt -NoNewline
Write-Output "Game.cpp Patched with Logs"
