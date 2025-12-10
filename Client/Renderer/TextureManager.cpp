#include "TextureManager.h"
#include <vector>

// extern void // LogDebug(const char *fmt, ...);

TextureManager::TextureManager(DX11Renderer *renderer) : m_renderer(renderer) {}

TextureManager::~TextureManager() { m_textureCache.clear(); }

ID3D11ShaderResourceView *
TextureManager::LoadTexture(const std::wstring &filename) {
  // Placeholder, we are using in-memory textures mainly.
  return nullptr;
}

ID3D11ShaderResourceView *
TextureManager::CreateTextureFromMemory(int width, int height, void *pData,
                                        bool useColorKey) {
  // Convert 16-bit 565 to 32-bit RGBA
  unsigned short *src = (unsigned short *)pData;
  std::vector<unsigned char> draggingData(width * height * 4);

  for (int i = 0; i < width * height; i++) {
    unsigned short pixel = src[i];

    // Extract RGB 565
    unsigned char r = (pixel & 0xF800) >> 11; // 5 bits (0-31)
    unsigned char g = (pixel & 0x07E0) >> 5;  // 6 bits (0-63)
    unsigned char b = (pixel & 0x001F);       // 5 bits (0-31)

    // CRITICAL FIX: Properly expand 5/6 bit values to 8 bit (0-255)
    unsigned char r8 = (r * 255) / 31; // Red: 5-bit to 8-bit
    unsigned char g8 = (g * 255) / 63; // Green: 6-bit to 8-bit
    unsigned char b8 = (b * 255) / 31; // Blue: 5-bit to 8-bit
    unsigned char a8 = 255;            // Alpha: fully opaque

    if (useColorKey && pixel == 0) // Assuming 0 is black/color key
    {
      a8 = 0;
      r8 = 0;
      g8 = 0;
      b8 = 0;
    }

    draggingData[i * 4 + 0] = r8;
    draggingData[i * 4 + 1] = g8;
    draggingData[i * 4 + 2] = b8;
    draggingData[i * 4 + 3] = a8;
  }

  D3D11_TEXTURE2D_DESC textureDesc;
  ZeroMemory(&textureDesc, sizeof(textureDesc));
  textureDesc.Width = width;
  textureDesc.Height = height;
  textureDesc.MipLevels = 1;
  textureDesc.ArraySize = 1;
  textureDesc.Format = DXGI_FORMAT_R8G8B8A8_UNORM;
  textureDesc.SampleDesc.Count = 1;
  textureDesc.SampleDesc.Quality = 0;
  textureDesc.Usage = D3D11_USAGE_DEFAULT;
  textureDesc.BindFlags = D3D11_BIND_SHADER_RESOURCE;
  textureDesc.CPUAccessFlags = 0;
  textureDesc.MiscFlags = 0;

  D3D11_SUBRESOURCE_DATA subResource;
  subResource.pSysMem = draggingData.data();
  subResource.SysMemPitch = width * 4;
  subResource.SysMemSlicePitch = 0;

  ComPtr<ID3D11Texture2D> texture;
  HRESULT result = m_renderer->GetDevice()->CreateTexture2D(
      &textureDesc, &subResource, &texture);
  if (FAILED(result))
    return nullptr;

  ID3D11ShaderResourceView *srv = nullptr;
  D3D11_SHADER_RESOURCE_VIEW_DESC srvDesc;
  ZeroMemory(&srvDesc, sizeof(srvDesc));
  srvDesc.Format = textureDesc.Format;
  srvDesc.ViewDimension = D3D11_SRV_DIMENSION_TEXTURE2D;
  srvDesc.Texture2D.MostDetailedMip = 0;
  srvDesc.Texture2D.MipLevels = 1;

  result = m_renderer->GetDevice()->CreateShaderResourceView(texture.Get(),
                                                             &srvDesc, &srv);
  if (FAILED(result))
    return nullptr;

  return srv;
}

void TextureManager::UpdateBackBuffer(void *pData, int srcPitch, int width,
                                      int height) {
  // // LogDebug("UpdateBackBuffer: Ptr=%p, Pitch=%d, W=%d, H=%d", pData, srcPitch,
  // width, height);

  if (!m_renderer || !m_renderer->GetDevice() || !m_renderer->GetContext()) {
    // // LogDebug("ERROR: Renderer/Device/Context is NULL");
    return;
  }

  if (!m_backBufferTexture) {
    // LogDebug("Creating Dynamic Texture...");
    D3D11_TEXTURE2D_DESC textureDesc;
    ZeroMemory(&textureDesc, sizeof(textureDesc));
    textureDesc.Width = width;
    textureDesc.Height = height;
    textureDesc.MipLevels = 1;
    textureDesc.ArraySize = 1;
    textureDesc.Format = DXGI_FORMAT_R8G8B8A8_UNORM;
    textureDesc.SampleDesc.Count = 1;
    textureDesc.SampleDesc.Quality = 0;
    textureDesc.Usage = D3D11_USAGE_DYNAMIC;
    textureDesc.BindFlags = D3D11_BIND_SHADER_RESOURCE;
    textureDesc.CPUAccessFlags = D3D11_CPU_ACCESS_WRITE;
    textureDesc.MiscFlags = 0;

    HRESULT result = m_renderer->GetDevice()->CreateTexture2D(
        &textureDesc, NULL, &m_backBufferTexture);
    if (FAILED(result)) {
      // LogDebug("Failed to CreateTexture2D: %x", result);
      return;
    }

    D3D11_SHADER_RESOURCE_VIEW_DESC srvDesc;
    ZeroMemory(&srvDesc, sizeof(srvDesc));
    srvDesc.Format = textureDesc.Format;
    srvDesc.ViewDimension = D3D11_SRV_DIMENSION_TEXTURE2D;
    srvDesc.Texture2D.MostDetailedMip = 0;
    srvDesc.Texture2D.MipLevels = 1;

    result = m_renderer->GetDevice()->CreateShaderResourceView(
        m_backBufferTexture.Get(), &srvDesc, &m_backBufferSRV);
    if (FAILED(result)) {
      // LogDebug("Failed to CreateSRV: %x", result);
      return;
    }
    // LogDebug("Texture Created Successfully.");
  }

  // Map
  D3D11_MAPPED_SUBRESOURCE mappedResource;
  HRESULT result = m_renderer->GetContext()->Map(m_backBufferTexture.Get(), 0,
                                                 D3D11_MAP_WRITE_DISCARD, 0,
                                                 &mappedResource);
  if (FAILED(result)) {
    // LogDebug("Failed to Map: %x", result);
    return;
  }

  // Convert
  unsigned short *src = (unsigned short *)pData;
  unsigned char *dest = (unsigned char *)mappedResource.pData;

  if (!dest) {
    // LogDebug("ERROR: Mapped Pointer is NULL");
    m_renderer->GetContext()->Unmap(m_backBufferTexture.Get(), 0);
    return;
  }

  for (int y = 0; y < height; y++) {
    unsigned short *rowSrc = src + (y * srcPitch);
    unsigned char *rowDest = dest + (y * mappedResource.RowPitch);

    for (int x = 0; x < width; x++) {
      unsigned short pixel = rowSrc[x];
      unsigned char r = (pixel & 0xF800) >> 11; // 5 bits (0-31)
      unsigned char g = (pixel & 0x07E0) >> 5;  // 6 bits (0-63)
      unsigned char b = (pixel & 0x001F);       // 5 bits (0-31)

      // CRITICAL FIX: Properly expand 5/6 bit values to 8 bit (0-255)
      // OLD METHOD: r << 3 gives max 248 (31 << 3 = 248) - TOO DARK!
      // NEW METHOD: Scale to full 0-255 range
      rowDest[x * 4 + 0] = (r * 255) / 31; // Red: 5-bit to 8-bit
      rowDest[x * 4 + 1] = (g * 255) / 63; // Green: 6-bit to 8-bit
      rowDest[x * 4 + 2] = (b * 255) / 31; // Blue: 5-bit to 8-bit
      rowDest[x * 4 + 3] = 255;            // Alpha: fully opaque
    }
  }

  m_renderer->GetContext()->Unmap(m_backBufferTexture.Get(), 0);
}
