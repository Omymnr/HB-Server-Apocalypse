#pragma once
#include "TextureAtlas.h"

class DX11Renderer;

// Global manager for all texture atlases
class TextureAtlasManager {
private:
  DX11Renderer *m_renderer;

  // Atlas per category
  TextureAtlas *m_atlases[ATLAS_COUNT];

  // Global lookup: sprite name -> atlas entry
  std::unordered_map<std::string, AtlasEntry *> m_globalLookup;

  bool m_initialized;

public:
  TextureAtlasManager();
  ~TextureAtlasManager();

  // Initialize with renderer
  bool Initialize(DX11Renderer *renderer);

  // Build atlas for a specific category
  bool BuildAtlas(AtlasCategory category,
                  const std::vector<SpriteDescriptor> &sprites);

  // Query atlas entry for a sprite
  AtlasEntry *GetAtlasEntry(const char *spriteName, int frame = 0);

  // Get atlas by category
  TextureAtlas *GetAtlas(AtlasCategory category);

  // Utilities
  void PrintStats();
  void Clear();

  // Singleton-style global access (optional)
  static TextureAtlasManager *GetInstance();
};

// Global pointer for easy access (like g_pRenderer)
extern TextureAtlasManager *g_pAtlasManager;
