$path = "d:\HB-Server-Apocalypse\Client\Game.h"
$txt = Get-Content $path -Raw
if ($txt -notmatch "DX11Renderer.h") {
    $txt = $txt.Replace('#include "DXC_ddraw.h"', "#include ""DXC_ddraw.h""`r`n#include ""Renderer/DX11Renderer.h""`r`n#include ""Renderer/SpriteBatcher.h""`r`n#include ""Renderer/TextureManager.h""")
    $txt = $txt.Replace('class DXC_ddraw  * m_pDraw;', "class DXC_ddraw  * m_pDraw;`r`n    DX11Renderer* m_Renderer;`r`n    SpriteBatcher* m_SpriteBatch;`r`n    TextureManager* m_TextureManager;")
    Set-Content $path $txt -NoNewline
    Write-Output "Game.h Modified Successfully"
} else {
    Write-Output "Game.h already modified"
}
