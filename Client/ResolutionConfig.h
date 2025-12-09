// ResolutionConfig.h - Dynamic Resolution Configuration System
#pragma once
#include <windows.h>

// UI Anchor types for dynamic positioning
enum UIAnchorType {
  ANCHOR_TOP_LEFT,      // (0, 0)
  ANCHOR_TOP_CENTER,    // (center_x, 0)
  ANCHOR_TOP_RIGHT,     // (width, 0)
  ANCHOR_CENTER_LEFT,   // (0, center_y)
  ANCHOR_CENTER,        // (center_x, center_y)
  ANCHOR_CENTER_RIGHT,  // (width, center_y)
  ANCHOR_BOTTOM_LEFT,   // (0, height)
  ANCHOR_BOTTOM_CENTER, // (center_x, height)
  ANCHOR_BOTTOM_RIGHT   // (width, height)
};

class CResolutionConfig {
public:
  // Screen dimensions
  int m_screenWidth;
  int m_screenHeight;
  int m_screenCenterX;
  int m_screenCenterY;

  // Viewport calculations (how many tiles fit on screen)
  int m_viewportTilesX;  // Horizontal tiles visible
  int m_viewportTilesY;  // Vertical tiles visible
  int m_viewportPixelsX; // Viewport width in pixels
  int m_viewportPixelsY; // Viewport height in pixels

  // Viewport offsets (for centering player)
  int m_viewportOffsetX; // X offset to center (screen_width/2)
  int m_viewportOffsetY; // Y offset to center (screen_height/2)

  // Aspect ratio
  float m_aspectRatio;

  // Constructor/Destructor
  CResolutionConfig();
  ~CResolutionConfig();

  // Initialization
  void Initialize(int width, int height);

  // Viewport calculation
  void CalculateViewport();

  // UI Anchoring
  void GetUIAnchor(UIAnchorType type, int *outX, int *outY);

  // Helper to get anchor with offset in one call
  void GetUIAnchorWithOffset(UIAnchorType type, int offsetX, int offsetY,
                             int *outX, int *outY);

  // Debug info
  void PrintDebugInfo();
};

// Global instance (extern declaration)
extern CResolutionConfig *g_pResolutionConfig;
