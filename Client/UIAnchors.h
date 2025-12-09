// UIAnchors.h - UI Anchoring System Helpers
#pragma once

// Macro helper for UI anchoring
// Usage: UI_ANCHOR(ANCHOR_TOP_RIGHT, -148, 50, &invX, &invY);
#define UI_ANCHOR(type, offsetX, offsetY, outX, outY)                          \
  do {                                                                         \
    int _ax, _ay;                                                              \
    if (g_pResolutionConfig) {                                                 \
      g_pResolutionConfig->GetUIAnchor(type, &_ax, &_ay);                      \
      *(outX) = _ax + (offsetX);                                               \
      *(outY) = _ay + (offsetY);                                               \
    } else {                                                                   \
      *(outX) = (offsetX);                                                     \
      *(outY) = (offsetY);                                                     \
    }                                                                          \
  } while (0)

// Compatibility defines for gradual migration
// These maintain 800x600 behavior until fully migrated
#define SCREEN_WIDTH                                                           \
  (g_pResolutionConfig ? g_pResolutionConfig->m_screenWidth : 800)
#define SCREEN_HEIGHT                                                          \
  (g_pResolutionConfig ? g_pResolutionConfig->m_screenHeight : 600)
#define SCREEN_CENTER_X                                                        \
  (g_pResolutionConfig ? g_pResolutionConfig->m_screenCenterX : 400)
#define SCREEN_CENTER_Y                                                        \
  (g_pResolutionConfig ? g_pResolutionConfig->m_screenCenterY : 300)

// Legacy compatibility (SCREENX/SCREENY used in some places)
#define SCREENX 0
#define SCREENY 0
