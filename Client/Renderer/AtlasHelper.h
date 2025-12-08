#pragma once
#include "TextureAtlasManager.h"
#include <string>
#include <vector>


// Helper class to demonstrate and simplify Texture Atlas usage
// This is a standalone system that doesn't modify CSprite
class AtlasHelper {
public:
  // Build an atlas from a list of sprite filenames
  // Example: BuildAtlasFromFiles(ATLAS_ITEMS, {"item-sword.spr",
  // "item-potion.spr"})
  static bool BuildAtlasFromFiles(AtlasCategory category,
                                  const std::vector<std::string> &spriteFiles);

  // Render a sprite directly from atlas (bypassing CSprite)
  // Returns true if sprite was found in atlas and rendered
  static bool RenderFromAtlas(const char *spriteName, int frame, float x,
                              float y, DX11Renderer *renderer);

  // Print atlas statistics to debug output
  static void PrintAtlasStats();

  // Example: Create a test atlas with some hardcoded sprites
  static bool BuildTestAtlas(DX11Renderer *renderer);

private:
  // Convert RGB565 pixel data to RGBA8888
  static void ConvertRGB565ToRGBA8888(const void *src565, uint32_t *dstRGBA,
                                      int width, int height, int pitch,
                                      uint16_t colorKey);
};
