#pragma once

#include "DX11Renderer.h"
#include <map>
#include <string>

class TextureManager {
public:
  TextureManager(DX11Renderer *renderer);
  ~TextureManager();

  // Load from disk (not implemented yet, used for new assets)
  ID3D11ShaderResourceView *LoadTexture(const std::wstring &filename);

  // Create from memory (Legacy 16-bit 565 support)
  // width/height: dimensions
  // pData: raw pixel data (WORD*)
  // useColorKey: if true, uses 0x0000 (black) as transparent
  ID3D11ShaderResourceView *
  CreateTextureFromMemory(int width, int height, void *pData, bool useColorKey);

  // Hybrid Mode Support
  // srcPitch: Pitch of the source data in WORDS (short*)
  void UpdateBackBuffer(void *pData, int srcPitch, int width, int height);
  ID3D11ShaderResourceView *GetBackBufferSRV() { return m_backBufferSRV.Get(); }

private:
  DX11Renderer *m_renderer;
  std::map<std::wstring, ComPtr<ID3D11ShaderResourceView>> m_textureCache;

  // Specific for Hybrid Backbuffer update
  ComPtr<ID3D11Texture2D> m_backBufferTexture;
  ComPtr<ID3D11ShaderResourceView> m_backBufferSRV;
};
