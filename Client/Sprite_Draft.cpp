
#include "Game.h" // For G_cSpriteAlphaDegree and global renderer access if needed
#include "Renderer/DX11Renderer.h"
#include "Renderer/TextureManager.h"
#include "Sprite.h"


extern DX11Renderer
    *g_pRenderer; // Assuming global access or I need to find where to get it.
// Game.cpp has m_pRenderer. I might need to access it differently.
// For now, I will assume I can get it from somewhere or add it to CSprite.
// Wait, CSprite has m_pDDraw (DXC_ddraw*).
// Does DXC_ddraw wrap DX11Renderer?
// If not, I should use the Global Renderer pointer if available.
// Let's assume there is a global 'extern DX11Renderer* g_pRenderer;' I can add
// to Game.cpp later if needed.

void CSprite::_EnsureDX11Texture() {
  if (m_pTexture != nullptr)
    return;
  if (m_bIsSurfaceEmpty) {
    if (_iOpenSprite() == false)
      return;
  }

  // Create Texture from DD7 Surface
  // We can assume m_lpSurface is valid here.
  // Lock surface to get pixels.
  DDSURFACEDESC2 ddsd;
  ZeroMemory(&ddsd, sizeof(ddsd));
  ddsd.dwSize = sizeof(ddsd);

  if (m_lpSurface->Lock(NULL, &ddsd, DDLOCK_WAIT, NULL) == DD_OK) {
    // Create DX11 Texture
    // We need the TextureManager.
    // Assuming g_pRenderer has GetTextureManager().
    // Or simplified: Just create it here manually if I have device?
    // Better: Use TextureManager::CreateTextureFromMemory.

    // ISSUE: I don't have easy access to TextureManager from CSprite without
    // passing it in. I will use a global accessor for now or access via Game
    // instance if possible. Let's assume extern CGame * g_pGame; or similar.

    // Temporarily, I will use QueryInterface/GetDevice from creating it here?
    // No, I need the Device. I will rely on 'extern DX11Renderer* g_pRenderer;'
    // being defined in DX11Renderer.cpp or Game.cpp

    if (g_pRenderer) {
      m_pTexture = g_pRenderer->GetTextureManager()->CreateTextureFromMemory(
          ddsd.dwWidth, ddsd.dwHeight, ddsd.lpSurface, true);
    }

    m_lpSurface->Unlock(NULL);
  }
}

// ... (Rest of file) ...

void CSprite::PutTransSpriteRGB(int sX, int sY, int sFrame, int sRed,
                                int sGreen, int sBlue, DWORD dwTime) {
  if (this == 0)
    return;
  if (m_stBrush == 0)
    return;
  if ((m_iTotalFrame - 1 < sFrame) || (sFrame < 0))
    return;

  // DX11 Optimization
  _EnsureDX11Texture();

  if (m_pTexture && g_pRenderer) {
    stBrush *pBrush = &m_stBrush[sFrame];

    // Calculate Destination
    float destX = (float)(sX + pBrush->pvx);
    float destY = (float)(sY + pBrush->pvy);
    float width = (float)pBrush->szx;
    float height = (float)pBrush->szy;

    // Source Rect from Atlas
    RECT srcRect;
    srcRect.left = pBrush->sx;
    srcRect.top = pBrush->sy;
    srcRect.right =
        pBrush
            ->szx; // CreateTextureFromMemory likely creates full size texture?
                   // Wait, m_stBrush has offsets into the PACKED surface.
    // My _EnsureDX11Texture creates a texture from the current m_lpSurface.
    // Is m_lpSurface the whole atlas or just one frame?
    // CMyDib loading suggests Atlas. "dwBitmapFileStartLoc".
    // CSprite constructor loads m_stBrush.
    // So yes, it is an atlas.

    // However, SpriteBatcher::DrawRect is designed for this!

    // Calculate UVs?
    // SpriteBatcher::DrawRect calculates UVs using Texture Width/Height?
    // Wait, my SpriteBatcher::DrawRect didn't implement UV calculation from
    // Rect yet! It merely passed srcRect to RenderCommand. And SortAndRender
    // was supposed to handle it. In Step 980 I wrote: "Placeholder UV logic
    // from srcRect if needed". I NEED TO IMPLEMENT THAT UV LOGIC!

    // I cannot finalize CSprite until SpriteBatcher handles UVs from RECT
    // correctly. I need Texture Dimensions to normalize UVs (u = x / texWidth).
    // I can pass Texture Dimensions to DrawRect?
    // Or Query resource.
  }
}
