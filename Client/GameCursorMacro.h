// GameCursorMacro.h - Macro to replace software cursor rendering with hardware
// cursor This simplifies the integration by providing a drop-in replacement

#pragma once
#include "GameCursorIntegration.h"
#include "SpriteID.h"

// Macro to replace: m_pSprite[DEF_SPRID_MOUSECURSOR]->PutSpriteFast(msX, msY,
// frame, dwTime); Usage: RENDER_GAME_CURSOR(msX, msY, frame, isDragging,
// dwTime);
#define RENDER_GAME_CURSOR(msX, msY, cursorFrame, isDragging, dwTime)          \
  do {                                                                         \
    if (!UpdateGameHardwareCursor(cursorFrame, isDragging)) {                  \
      /* Hardware cursor not active, use software cursor */                    \
      if (m_pSprite[DEF_SPRID_MOUSECURSOR]) {                                  \
        m_pSprite[DEF_SPRID_MOUSECURSOR]->PutSpriteFast(msX, msY, cursorFrame, \
                                                        dwTime);               \
      }                                                                        \
    }                                                                          \
  } while (0)

// Simpler version for places where we know it's not dragging
#define RENDER_GAME_CURSOR_SIMPLE(msX, msY, cursorFrame, dwTime)               \
  RENDER_GAME_CURSOR(msX, msY, cursorFrame, false, dwTime)
