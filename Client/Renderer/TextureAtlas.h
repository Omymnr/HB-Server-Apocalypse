#pragma once
#include "RectanglePacker.h"
#include <d3d11.h>
#include <string>
#include <unordered_map>
#include <vector>
#include <wrl/client.h>


using Microsoft::WRL::ComPtr;

class DX11Renderer;

// Forward declarations
struct AtlasEntry;

// Categories of texture atlases
enum AtlasCategory { ATLAS_ITEMS, ATLAS_UI, ATLAS_EFFECTS, ATLAS_COUNT };

// Descriptor for a sprite to be added to atlas
struct SpriteDescriptor {
  std::string name;
  int frame;
  int width;
  int height;
  POINT pivot;
  std::vector<uint32_t> pixelData; // RGBA8888
};

// Entry in the texture atlas
struct AtlasEntry {
  char spriteName[64];
  int spriteFrame;

  // Position in atlas (pixels)
  RECT atlasRect;

  // Normalized UVs [0.0 - 1.0]
  struct {
    float u0, v0; // Top-left
    float u1, v1; // Bottom-right
  } uv;

  // Original sprite data
  int originalWidth;
  int originalHeight;
  POINT pivot;

  // Reference to parent atlas
  class TextureAtlas *parentAtlas;
};

// Single texture atlas containing multiple sprites
class TextureAtlas {
private:
  DX11Renderer *m_renderer;

  int m_width;
  int m_height;
  AtlasCategory m_category;

  // GPU Resources
  ComPtr<ID3D11Texture2D> m_atlasTexture;
  ComPtr<ID3D11ShaderResourceView> m_atlasSRV;

  // CPU-side pixel data (for construction)
  std::vector<uint32_t> m_pixelData;

  // Packing algorithm
  RectanglePacker m_packer;

  // Entries in this atlas
  std::vector<AtlasEntry> m_entries;

  // Fast lookup: "spritename_frame" -> entry
  std::unordered_map<std::string, AtlasEntry *> m_entryLookup;

  bool m_isBuilt;

public:
  TextureAtlas(DX11Renderer *renderer, int width, int height,
               AtlasCategory category);
  ~TextureAtlas();

  // Add sprite to atlas (must be called before Build)
  bool AddSprite(const SpriteDescriptor &sprite);

  // Build GPU texture from packed sprites
  bool Build();

  // Query
  AtlasEntry *GetEntry(const char *spriteName, int frame);
  ID3D11ShaderResourceView *GetSRV() { return m_atlasSRV.Get(); }

  // Info
  int GetWidth() const { return m_width; }
  int GetHeight() const { return m_height; }
  int GetSpriteCount() const { return (int)m_entries.size(); }
  float GetEfficiency() const { return m_packer.GetEfficiency(); }
  AtlasCategory GetCategory() const { return m_category; }

  // Debug
  void SaveToDisk(const char *filename);
  void Clear();

private:
  // Copy sprite pixels to atlas pixel buffer
  void CopyPixelsToAtlas(const std::vector<uint32_t> &srcPixels, int srcWidth,
                         int srcHeight, int dstX, int dstY);

  // Calculate normalized UVs
  void CalculateUVs(const RECT &atlasRect, float &u0, float &v0, float &u1,
                    float &v1);

  // Create GPU texture from pixel data
  bool CreateGPUTexture();
};
