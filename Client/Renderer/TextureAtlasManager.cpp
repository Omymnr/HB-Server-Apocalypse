#include "TextureAtlasManager.h"
#include "DX11Renderer.h"
#include <algorithm>
#include <stdio.h>

// Global instance
TextureAtlasManager *g_pAtlasManager = nullptr;

TextureAtlasManager::TextureAtlasManager()
    : m_renderer(nullptr), m_initialized(false) {

  for (int i = 0; i < ATLAS_COUNT; i++) {
    m_atlases[i] = nullptr;
  }
}

TextureAtlasManager::~TextureAtlasManager() { Clear(); }

bool TextureAtlasManager::Initialize(DX11Renderer *renderer) {
  if (m_initialized) {
    return true;
  }

  if (!renderer) {
    return false;
  }

  m_renderer = renderer;

  // Create atlas for each category with appropriate sizes
  m_atlases[ATLAS_ITEMS] = new TextureAtlas(renderer, 2048, 2048, ATLAS_ITEMS);
  m_atlases[ATLAS_UI] = new TextureAtlas(renderer, 1024, 1024, ATLAS_UI);
  m_atlases[ATLAS_EFFECTS] =
      new TextureAtlas(renderer, 2048, 2048, ATLAS_EFFECTS);

  m_initialized = true;

  OutputDebugStringA("TextureAtlasManager: Initialized\n");
  return true;
}

bool TextureAtlasManager::BuildAtlas(
    AtlasCategory category, const std::vector<SpriteDescriptor> &sprites) {
  if (!m_initialized || category < 0 || category >= ATLAS_COUNT) {
    return false;
  }

  TextureAtlas *atlas = m_atlases[category];
  if (!atlas) {
    return false;
  }

  // Sort sprites by height (descending) for better packing
  std::vector<SpriteDescriptor> sorted = sprites;
  std::sort(sorted.begin(), sorted.end(),
            [](const SpriteDescriptor &a, const SpriteDescriptor &b) {
              return a.height > b.height;
            });

  // Add all sprites to atlas
  int successCount = 0;
  for (const auto &sprite : sorted) {
    if (atlas->AddSprite(sprite)) {
      successCount++;
    }
  }

  if (successCount == 0) {
    char msg[256];
    sprintf(msg, "TextureAtlasManager: No sprites added to atlas category %d\n",
            category);
    OutputDebugStringA(msg);
    return false;
  }

  // Build GPU texture
  if (!atlas->Build()) {
    return false;
  }

  char msg[256];
  sprintf(msg,
          "TextureAtlasManager: Built atlas category %d with %d/%d sprites\n",
          category, successCount, (int)sprites.size());
  OutputDebugStringA(msg);

  return true;
}

AtlasEntry *TextureAtlasManager::GetAtlasEntry(const char *spriteName,
                                               int frame) {
  if (!m_initialized) {
    return nullptr;
  }

  // Try each atlas
  for (int i = 0; i < ATLAS_COUNT; i++) {
    if (m_atlases[i]) {
      AtlasEntry *entry = m_atlases[i]->GetEntry(spriteName, frame);
      if (entry) {
        return entry;
      }
    }
  }

  return nullptr;
}

TextureAtlas *TextureAtlasManager::GetAtlas(AtlasCategory category) {
  if (category < 0 || category >= ATLAS_COUNT) {
    return nullptr;
  }
  return m_atlases[category];
}

void TextureAtlasManager::PrintStats() {
  if (!m_initialized) {
    OutputDebugStringA("TextureAtlasManager: Not initialized\n");
    return;
  }

  OutputDebugStringA("\n=== Texture Atlas Statistics ===\n");

  const char *categoryNames[] = {"Items", "UI", "Effects"};

  for (int i = 0; i < ATLAS_COUNT; i++) {
    if (m_atlases[i]) {
      char msg[256];
      sprintf(msg, "%s Atlas: %dx%d, %d sprites, %.1f%% efficient\n",
              categoryNames[i], m_atlases[i]->GetWidth(),
              m_atlases[i]->GetHeight(), m_atlases[i]->GetSpriteCount(),
              m_atlases[i]->GetEfficiency() * 100.0f);
      OutputDebugStringA(msg);
    }
  }

  OutputDebugStringA("================================\n\n");
}

void TextureAtlasManager::Clear() {
  for (int i = 0; i < ATLAS_COUNT; i++) {
    if (m_atlases[i]) {
      delete m_atlases[i];
      m_atlases[i] = nullptr;
    }
  }

  m_globalLookup.clear();
  m_initialized = false;
}

TextureAtlasManager *TextureAtlasManager::GetInstance() {
  return g_pAtlasManager;
}
