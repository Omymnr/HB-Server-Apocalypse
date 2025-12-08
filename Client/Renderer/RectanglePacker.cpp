#include "RectanglePacker.h"
#include <algorithm>
#include <limits.h>

RectanglePacker::RectanglePacker(int atlasWidth, int atlasHeight)
    : m_atlasWidth(atlasWidth), m_atlasHeight(atlasHeight), m_usedArea(0) {
  // Initialize with single skyline at bottom
  Skyline initial;
  initial.x = 0;
  initial.y = 0;
  initial.width = atlasWidth;
  m_skylines.push_back(initial);
}

bool RectanglePacker::Pack(int width, int height, PackedRect &outRect) {
  int bestY = INT_MAX;
  int bestIndex = -1;

  // Find best position using skyline algorithm
  bestY = FindBestPosition(width, height, bestY, bestIndex);

  if (bestIndex == -1) {
    return false; // Doesn't fit
  }

  // Place rectangle
  outRect.x = m_skylines[bestIndex].x;
  outRect.y = bestY;
  outRect.width = width;
  outRect.height = height;

  // Update skyline
  UpdateSkyline(bestIndex, outRect);

  // Track used area
  m_usedArea += width * height;

  return true;
}

int RectanglePacker::FindBestPosition(int width, int height, int &outY,
                                      int &outIndex) {
  int bestY = INT_MAX;
  int bestIndex = -1;
  int bestX = 0;

  for (int i = 0; i < (int)m_skylines.size(); i++) {
    int y = CanFit(i, width, height);

    if (y >= 0) {
      // Check if this position is better (lower Y = better packing)
      if (y < bestY) {
        bestY = y;
        bestIndex = i;
        bestX = m_skylines[i].x;
      }
    }
  }

  outY = bestY;
  outIndex = bestIndex;
  return bestY;
}

int RectanglePacker::CanFit(int skylineIndex, int width, int height) {
  int x = m_skylines[skylineIndex].x;

  if (x + width > m_atlasWidth) {
    return -1; // Doesn't fit horizontally
  }

  int widthLeft = width;
  int i = skylineIndex;
  int y = m_skylines[skylineIndex].y;

  // Check if rectangle fits across multiple skylines
  while (widthLeft > 0) {
    if (i >= (int)m_skylines.size()) {
      return -1; // Ran out of skylines
    }

    // Take maximum Y across all segments the rectangle spans
    y = max(y, m_skylines[i].y);

    if (y + height > m_atlasHeight) {
      return -1; // Doesn't fit vertically
    }

    widthLeft -= m_skylines[i].width;
    i++;
  }

  return y;
}

void RectanglePacker::UpdateSkyline(int index, const PackedRect &rect) {
  Skyline newSkyline;
  newSkyline.x = rect.x;
  newSkyline.y = rect.y + rect.height;
  newSkyline.width = rect.width;

  // Insert new skyline
  m_skylines.insert(m_skylines.begin() + index, newSkyline);

  // Remove or trim skylines covered by the new rectangle
  int i = index + 1;
  while (i < (int)m_skylines.size()) {
    if (m_skylines[i].x < rect.x + rect.width) {
      // Skyline is covered by rectangle
      int shrink = rect.x + rect.width - m_skylines[i].x;

      if (m_skylines[i].width <= shrink) {
        // Completely covered, remove it
        m_skylines.erase(m_skylines.begin() + i);
      } else {
        // Partially covered, shrink it
        m_skylines[i].x += shrink;
        m_skylines[i].width -= shrink;
        break;
      }
    } else {
      break;
    }
  }

  // Merge adjacent skylines with same height
  MergeSkylines();
}

void RectanglePacker::MergeSkylines() {
  for (int i = 0; i < (int)m_skylines.size() - 1; i++) {
    if (m_skylines[i].y == m_skylines[i + 1].y) {
      // Merge with next
      m_skylines[i].width += m_skylines[i + 1].width;
      m_skylines.erase(m_skylines.begin() + i + 1);
      i--; // Check again
    }
  }
}

void RectanglePacker::Clear() {
  m_skylines.clear();
  m_usedArea = 0;

  // Reset to initial state
  Skyline initial;
  initial.x = 0;
  initial.y = 0;
  initial.width = m_atlasWidth;
  m_skylines.push_back(initial);
}

float RectanglePacker::GetEfficiency() const {
  int totalArea = m_atlasWidth * m_atlasHeight;
  if (totalArea == 0)
    return 0.0f;
  return (float)m_usedArea / (float)totalArea;
}
