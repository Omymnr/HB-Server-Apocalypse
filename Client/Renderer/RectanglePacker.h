#pragma once
#include <Windows.h>
#include <vector>


// Rectangle Packing Algorithm - Skyline Bottom-Left
// Efficiently packs rectangles into a larger atlas texture
class RectanglePacker {
public:
  struct PackedRect {
    int x, y, width, height;
  };

private:
  struct Skyline {
    int x;     // Start position
    int y;     // Height at this position
    int width; // Width of this segment
  };

  int m_atlasWidth;
  int m_atlasHeight;
  std::vector<Skyline> m_skylines;
  int m_usedArea;

public:
  RectanglePacker(int atlasWidth, int atlasHeight);

  // Try to pack a rectangle into the atlas
  // Returns true if successful, fills outRect with position
  bool Pack(int width, int height, PackedRect &outRect);

  // Clear all packed rectangles
  void Clear();

  // Get packing efficiency (0.0 - 1.0)
  float GetEfficiency() const;

  // Get number of skyline segments (for debugging)
  int GetSkylineCount() const { return (int)m_skylines.size(); }

private:
  // Find best position for rectangle
  int FindBestPosition(int width, int height, int &outY, int &outIndex);

  // Check if rectangle fits at given skyline position
  int CanFit(int skylineIndex, int width, int height);

  // Update skyline after placing rectangle
  void UpdateSkyline(int index, const PackedRect &rect);

  // Merge adjacent skylines with same height
  void MergeSkylines();
};
