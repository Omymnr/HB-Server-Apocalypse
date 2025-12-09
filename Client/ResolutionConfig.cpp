// ResolutionConfig.cpp - Dynamic Resolution Configuration Implementation
#include "ResolutionConfig.h"
#include <stdio.h>

// Global instance
CResolutionConfig *g_pResolutionConfig = nullptr;

CResolutionConfig::CResolutionConfig() {
  m_screenWidth = 800;
  m_screenHeight = 600;
  m_screenCenterX = 400;
  m_screenCenterY = 300;
  m_viewportTilesX = 27;
  m_viewportTilesY = 21;
  m_viewportPixelsX = 800;
  m_viewportPixelsY = 600;
  m_viewportOffsetX = 400;
  m_viewportOffsetY = 300;
  m_aspectRatio = 1.333f;
}

CResolutionConfig::~CResolutionConfig() {
  // Nothing to cleanup
}

void CResolutionConfig::Initialize(int width, int height) {
  m_screenWidth = width;
  m_screenHeight = height;
  m_screenCenterX = width / 2;
  m_screenCenterY = height / 2;

  // Calculate aspect ratio
  m_aspectRatio = (float)width / (float)height;

  // Calculate viewport
  CalculateViewport();

  // Debug output
  PrintDebugInfo();
}

void CResolutionConfig::CalculateViewport() {
  // Calculate how many tiles fit on screen
  // Add +2 for scrolling buffer
  m_viewportTilesX = (m_screenWidth / 32) + 2;
  m_viewportTilesY = (m_screenHeight / 32) + 2;

  // Calculate viewport size in pixels
  m_viewportPixelsX = m_viewportTilesX * 32;
  m_viewportPixelsY = m_viewportTilesY * 32;

  // Offsets to center player on screen
  m_viewportOffsetX = m_screenCenterX;
  m_viewportOffsetY = m_screenCenterY;

  char msg[512];
  sprintf(
      msg,
      "ResolutionConfig: Viewport calculated - %dx%d tiles (%dx%d pixels)\n",
      m_viewportTilesX, m_viewportTilesY, m_viewportPixelsX, m_viewportPixelsY);
  OutputDebugStringA(msg);
}

void CResolutionConfig::GetUIAnchor(UIAnchorType type, int *outX, int *outY) {
  switch (type) {
  case ANCHOR_TOP_LEFT:
    *outX = 0;
    *outY = 0;
    break;

  case ANCHOR_TOP_CENTER:
    *outX = m_screenCenterX;
    *outY = 0;
    break;

  case ANCHOR_TOP_RIGHT:
    *outX = m_screenWidth;
    *outY = 0;
    break;

  case ANCHOR_CENTER_LEFT:
    *outX = 0;
    *outY = m_screenCenterY;
    break;

  case ANCHOR_CENTER:
    *outX = m_screenCenterX;
    *outY = m_screenCenterY;
    break;

  case ANCHOR_CENTER_RIGHT:
    *outX = m_screenWidth;
    *outY = m_screenCenterY;
    break;

  case ANCHOR_BOTTOM_LEFT:
    *outX = 0;
    *outY = m_screenHeight;
    break;

  case ANCHOR_BOTTOM_CENTER:
    *outX = m_screenCenterX;
    *outY = m_screenHeight;
    break;

  case ANCHOR_BOTTOM_RIGHT:
    *outX = m_screenWidth;
    *outY = m_screenHeight;
    break;

  default:
    // Fallback to center
    *outX = m_screenCenterX;
    *outY = m_screenCenterY;
    break;
  }
}

void CResolutionConfig::GetUIAnchorWithOffset(UIAnchorType type, int offsetX,
                                              int offsetY, int *outX,
                                              int *outY) {
  int anchorX, anchorY;
  GetUIAnchor(type, &anchorX, &anchorY);
  *outX = anchorX + offsetX;
  *outY = anchorY + offsetY;
}

void CResolutionConfig::PrintDebugInfo() {
  char msg[512];
  sprintf(msg,
          "===== Resolution Configuration =====\n"
          "Screen: %dx%d (center: %d,%d)\n"
          "Aspect Ratio: %.3f\n"
          "Viewport Tiles: %dx%d\n"
          "Viewport Pixels: %dx%d\n"
          "Viewport Offsets: %d,%d\n"
          "====================================\n",
          m_screenWidth, m_screenHeight, m_screenCenterX, m_screenCenterY,
          m_aspectRatio, m_viewportTilesX, m_viewportTilesY, m_viewportPixelsX,
          m_viewportPixelsY, m_viewportOffsetX, m_viewportOffsetY);
  OutputDebugStringA(msg);
}
