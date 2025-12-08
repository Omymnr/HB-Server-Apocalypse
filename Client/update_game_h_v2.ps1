$path = "d:\HB-Server-Apocalypse\Client\Game.h"
$txt = Get-Content $path -Raw

# Inject Headers
if ($txt -notmatch "DX11Renderer.h") {
    $txt = $txt -replace '#include "DXC_ddraw.h"', "#include ""DXC_ddraw.h""`r`n#include ""Renderer/DX11Renderer.h""`r`n#include ""Renderer/SpriteBatcher.h""`r`n#include ""Renderer/TextureManager.h"""
    Write-Output "Headers Injected"
}

# Inject Members
# Regex to match 'class DXC_ddraw  m_DDraw;' with variable spacing
if ($txt -notmatch "DX11Renderer\* m_Renderer;") {
    $txt = $txt -replace 'class\s+DXC_ddraw\s+m_DDraw;', "class DXC_ddraw  m_DDraw;`r`n    DX11Renderer* m_Renderer;`r`n    SpriteBatcher* m_SpriteBatch;`r`n    TextureManager* m_TextureManager;"
    Write-Output "Members Injected"
}

Set-Content $path $txt -NoNewline
Write-Output "Game.h Updated"
