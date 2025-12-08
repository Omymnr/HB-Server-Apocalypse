#include "TextureAtlas.h"
#include "DX11Renderer.h"
#include <algorithm>
#include <stdio.h>

TextureAtlas::TextureAtlas(DX11Renderer *renderer, int width, int height,
                           AtlasCategory category)
    : m_renderer(renderer), m_width(width), m_height(height),
      m_category(category), m_packer(width, height), m_isBuilt(false) {

  // Allocate pixel buffer
  m_pixelData.resize(width * height,
                     0xFF000000); // Initialize to black with alpha
}

TextureAtlas::~TextureAtlas() { Clear(); }

bool TextureAtlas::AddSprite(const SpriteDescriptor &sprite) {
  if (m_isBuilt) {
    // Can't add sprites after building
    return false;
  }

  // Add 1px padding to prevent texture bleeding
  const int PADDING = 1;
  int paddedWidth = sprite.width + PADDING * 2;
  int paddedHeight = sprite.height + PADDING * 2;

  // Try to pack into atlas
  RectanglePacker::PackedRect rect;
  if (!m_packer.Pack(paddedWidth, paddedHeight, rect)) {
    // Doesn't fit
    char msg[256];
    sprintf(msg,
            "TextureAtlas: Sprite '%s' frame %d doesn't fit in %dx%d atlas",
            sprite.name.c_str(), sprite.frame, m_width, m_height);
    OutputDebugStringA(msg);
    return false;
  }

  // Copy pixels to atlas (with padding offset)
  CopyPixelsToAtlas(sprite.pixelData, sprite.width, sprite.height,
                    rect.x + PADDING, rect.y + PADDING);

  // Create entry
  AtlasEntry entry;
  strncpy_s(entry.spriteName, sizeof(entry.spriteName), sprite.name.c_str(),
            _TRUNCATE);
  entry.spriteFrame = sprite.frame;
  entry.atlasRect.left = rect.x + PADDING;
  entry.atlasRect.top = rect.y + PADDING;
  entry.atlasRect.right = rect.x + PADDING + sprite.width;
  entry.atlasRect.bottom = rect.y + PADDING + sprite.height;
  entry.originalWidth = sprite.width;
  entry.originalHeight = sprite.height;
  entry.pivot = sprite.pivot;
  entry.parentAtlas = this;

  // Calculate UVs
  CalculateUVs(entry.atlasRect, entry.uv.u0, entry.uv.v0, entry.uv.u1,
               entry.uv.v1);

  // Store entry
  m_entries.push_back(entry);

  // Index for fast lookup
  std::string key = sprite.name + "_" + std::to_string(sprite.frame);
  m_entryLookup[key] = &m_entries.back();

  return true;
}

bool TextureAtlas::Build() {
  if (m_isBuilt) {
    return true; // Already built
  }

  if (m_entries.empty()) {
    OutputDebugStringA("TextureAtlas: No sprites to build");
    return false;
  }

  // Create GPU texture
  if (!CreateGPUTexture()) {
    return false;
  }

  // Free CPU pixel data (no longer needed)
  m_pixelData.clear();
  m_pixelData.shrink_to_fit();

  m_isBuilt = true;

  // Log success
  char msg[256];
  sprintf(
      msg,
      "TextureAtlas: Built %dx%d atlas with %d sprites (%.1f%% efficient)\n",
      m_width, m_height, GetSpriteCount(), GetEfficiency() * 100.0f);
  OutputDebugStringA(msg);

  return true;
}

AtlasEntry *TextureAtlas::GetEntry(const char *spriteName, int frame) {
  std::string key = std::string(spriteName) + "_" + std::to_string(frame);
  auto it = m_entryLookup.find(key);
  if (it != m_entryLookup.end()) {
    return it->second;
  }
  return nullptr;
}

void TextureAtlas::CopyPixelsToAtlas(const std::vector<uint32_t> &srcPixels,
                                     int srcWidth, int srcHeight, int dstX,
                                     int dstY) {
  for (int y = 0; y < srcHeight; y++) {
    for (int x = 0; x < srcWidth; x++) {
      int srcIndex = y * srcWidth + x;
      int dstIndex = (dstY + y) * m_width + (dstX + x);

      if (dstIndex >= 0 && dstIndex < (int)m_pixelData.size()) {
        m_pixelData[dstIndex] = srcPixels[srcIndex];
      }
    }
  }
}

void TextureAtlas::CalculateUVs(const RECT &atlasRect, float &u0, float &v0,
                                float &u1, float &v1) {
  // Normalize to [0.0, 1.0]
  u0 = (float)atlasRect.left / (float)m_width;
  v0 = (float)atlasRect.top / (float)m_height;
  u1 = (float)atlasRect.right / (float)m_width;
  v1 = (float)atlasRect.bottom / (float)m_height;

  // Optional: Half-pixel offset to prevent bleeding with bilinear filtering
  float halfPixelU = 0.5f / m_width;
  float halfPixelV = 0.5f / m_height;
  u0 += halfPixelU;
  v0 += halfPixelV;
  u1 -= halfPixelU;
  v1 -= halfPixelV;
}

bool TextureAtlas::CreateGPUTexture() {
  D3D11_TEXTURE2D_DESC desc;
  ZeroMemory(&desc, sizeof(desc));
  desc.Width = m_width;
  desc.Height = m_height;
  desc.MipLevels = 1;
  desc.ArraySize = 1;
  desc.Format = DXGI_FORMAT_R8G8B8A8_UNORM;
  desc.SampleDesc.Count = 1;
  desc.SampleDesc.Quality = 0;
  desc.Usage = D3D11_USAGE_IMMUTABLE; // Won't change after creation
  desc.BindFlags = D3D11_BIND_SHADER_RESOURCE;
  desc.CPUAccessFlags = 0;

  D3D11_SUBRESOURCE_DATA initData;
  initData.pSysMem = m_pixelData.data();
  initData.SysMemPitch = m_width * 4; // RGBA = 4 bytes per pixel
  initData.SysMemSlicePitch = 0;

  HRESULT hr = m_renderer->GetDevice()->CreateTexture2D(&desc, &initData,
                                                        &m_atlasTexture);
  if (FAILED(hr)) {
    OutputDebugStringA("TextureAtlas: Failed to create D3D11 texture");
    return false;
  }

  // Create shader resource view
  D3D11_SHADER_RESOURCE_VIEW_DESC srvDesc;
  ZeroMemory(&srvDesc, sizeof(srvDesc));
  srvDesc.Format = desc.Format;
  srvDesc.ViewDimension = D3D11_SRV_DIMENSION_TEXTURE2D;
  srvDesc.Texture2D.MostDetailedMip = 0;
  srvDesc.Texture2D.MipLevels = 1;

  hr = m_renderer->GetDevice()->CreateShaderResourceView(m_atlasTexture.Get(),
                                                         &srvDesc, &m_atlasSRV);
  if (FAILED(hr)) {
    OutputDebugStringA("TextureAtlas: Failed to create SRV");
    return false;
  }

  return true;
}

void TextureAtlas::Clear() {
  m_entries.clear();
  m_entryLookup.clear();
  m_pixelData.clear();
  m_packer.Clear();
  m_isBuilt = false;
}

void TextureAtlas::SaveToDisk(const char *filename) {
  // TODO: Implement BMP/PNG save for debugging
  // For now, just log
  char msg[256];
  sprintf(msg, "TextureAtlas: Would save %dx%d atlas to '%s'\n", m_width,
          m_height, filename);
  OutputDebugStringA(msg);
}
