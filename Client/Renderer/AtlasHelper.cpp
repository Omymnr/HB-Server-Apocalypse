#include "AtlasHelper.h"
#include "DX11Renderer.h"
#include "SpriteBatcher.h"
#include <stdio.h>

bool AtlasHelper::BuildAtlasFromFiles(
    AtlasCategory category, const std::vector<std::string> &spriteFiles) {
  if (!g_pAtlasManager) {
    OutputDebugStringA("AtlasHelper: Atlas manager not initialized\n");
    return false;
  }

  std::vector<SpriteDescriptor> sprites;

  // TODO: Load sprite files and convert to SpriteDescriptor
  // This would require:
  // 1. Open .spr file
  // 2. Parse header and frames
  // 3. Extract pixel data
  // 4. Convert RGB565 to RGBA8888
  // 5. Add to sprites vector

  // For now, just log
  char msg[256];
  sprintf(msg, "AtlasHelper: Would load %d sprite files for category %d\n",
          (int)spriteFiles.size(), category);
  OutputDebugStringA(msg);

  if (sprites.empty()) {
    OutputDebugStringA(
        "AtlasHelper: No sprites loaded (not implemented yet)\n");
    return false;
  }

  return g_pAtlasManager->BuildAtlas(category, sprites);
}

bool AtlasHelper::RenderFromAtlas(const char *spriteName, int frame, float x,
                                  float y, DX11Renderer *renderer) {
  if (!g_pAtlasManager || !renderer) {
    return false;
  }

  AtlasEntry *entry = g_pAtlasManager->GetAtlasEntry(spriteName, frame);
  if (!entry) {
    return false;
  }

  // Get sprite dimensions from entry
  float width = (float)entry->originalWidth;
  float height = (float)entry->originalHeight;

  // Get SRV from parent atlas
  ID3D11ShaderResourceView *atlasSRV = entry->parentAtlas->GetSRV();

  // Render using SpriteBatcher
  renderer->GetSpriteBatcher()->DrawRect(
      atlasSRV, entry->atlasRect, (float)entry->parentAtlas->GetWidth(),
      (float)entry->parentAtlas->GetHeight(), x, y, width, height, 1.0f, 1.0f,
      1.0f, 1.0f, // White color
      DX11Renderer::BLEND_ALPHA);

  return true;
}

void AtlasHelper::PrintAtlasStats() {
  if (!g_pAtlasManager) {
    OutputDebugStringA("AtlasHelper: Atlas manager not initialized\n");
    return;
  }

  g_pAtlasManager->PrintStats();
}

bool AtlasHelper::BuildTestAtlas(DX11Renderer *renderer) {
  if (!g_pAtlasManager) {
    OutputDebugStringA("AtlasHelper: Initializing atlas manager...\n");
    g_pAtlasManager = new TextureAtlasManager();
    if (!g_pAtlasManager->Initialize(renderer)) {
      OutputDebugStringA("AtlasHelper: Failed to initialize atlas manager\n");
      return false;
    }
  }

  // Create some test sprites with dummy data
  std::vector<SpriteDescriptor> testSprites;

  // Create a simple 32x32 red square
  SpriteDescriptor redSquare;
  redSquare.name = "test_red_square";
  redSquare.frame = 0;
  redSquare.width = 32;
  redSquare.height = 32;
  redSquare.pivot = {0, 0};
  redSquare.pixelData.resize(32 * 32);

  // Fill with red color (RGBA: 0xFF0000FF)
  for (int i = 0; i < 32 * 32; i++) {
    redSquare.pixelData[i] = 0xFF0000FF; // Red, full alpha
  }

  testSprites.push_back(redSquare);

  // Create a simple 64x64 green square
  SpriteDescriptor greenSquare;
  greenSquare.name = "test_green_square";
  greenSquare.frame = 0;
  greenSquare.width = 64;
  greenSquare.height = 64;
  greenSquare.pivot = {0, 0};
  greenSquare.pixelData.resize(64 * 64);

  // Fill with green color (RGBA: 0x00FF00FF)
  for (int i = 0; i < 64 * 64; i++) {
    greenSquare.pixelData[i] = 0x00FF00FF; // Green, full alpha
  }

  testSprites.push_back(greenSquare);

  // Create a simple 48x48 blue square
  SpriteDescriptor blueSquare;
  blueSquare.name = "test_blue_square";
  blueSquare.frame = 0;
  blueSquare.width = 48;
  blueSquare.height = 48;
  blueSquare.pivot = {0, 0};
  blueSquare.pixelData.resize(48 * 48);

  // Fill with blue color (RGBA: 0x0000FFFF)
  for (int i = 0; i < 48 * 48; i++) {
    blueSquare.pixelData[i] = 0x0000FFFF; // Blue, full alpha
  }

  testSprites.push_back(blueSquare);

  // Build the test atlas
  OutputDebugStringA(
      "AtlasHelper: Building test atlas with 3 colored squares...\n");
  bool result = g_pAtlasManager->BuildAtlas(ATLAS_ITEMS, testSprites);

  if (result) {
    OutputDebugStringA("AtlasHelper: Test atlas built successfully!\n");
    g_pAtlasManager->PrintStats();
  }

  return result;
}

void AtlasHelper::ConvertRGB565ToRGBA8888(const void *src565, uint32_t *dstRGBA,
                                          int width, int height, int pitch,
                                          uint16_t colorKey) {
  const uint16_t *src = (const uint16_t *)src565;

  for (int y = 0; y < height; y++) {
    for (int x = 0; x < width; x++) {
      uint16_t pixel = src[y * pitch + x];

      if (pixel == colorKey) {
        // Transparent pixel
        dstRGBA[y * width + x] = 0x00000000;
      } else {
        // Extract RGB components (5-6-5 format)
        uint8_t r = (pixel & 0xF800) >> 11; // 5 bits
        uint8_t g = (pixel & 0x07E0) >> 5;  // 6 bits
        uint8_t b = (pixel & 0x001F);       // 5 bits

        // Expand to 8-bit with bit replication
        uint8_t r8 = (r << 3) | (r >> 2);
        uint8_t g8 = (g << 2) | (g >> 4);
        uint8_t b8 = (b << 3) | (b >> 2);

        // Pack as RGBA8888 (ABGR in memory for little-endian)
        dstRGBA[y * width + x] = (0xFF << 24) | (b8 << 16) | (g8 << 8) | r8;
      }
    }
  }
}
