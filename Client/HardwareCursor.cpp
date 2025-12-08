// HardwareCursor.cpp - Hardware Cursor Management Implementation
#include "HardwareCursor.h"
#include <windows.h>

// Global instance
CHardwareCursor *g_pHardwareCursor = nullptr;

CHardwareCursor::CHardwareCursor() {
  m_bEnabled = true;
  m_hCurrentCursor = nullptr;
  m_hCursorNormal = nullptr;
  m_hCursorAttack = nullptr;
  m_hCursorSelect = nullptr;
  m_hCursorProhibit = nullptr;
  m_hCursorSpell = nullptr;
}

CHardwareCursor::~CHardwareCursor() { Cleanup(); }

void CHardwareCursor::Initialize() {
  // Load standard Windows cursors mapped to game states
  m_hCursorNormal = LoadCursor(NULL, IDC_ARROW); // Default arrow
  m_hCursorAttack = LoadCursor(NULL, IDC_CROSS); // Attack/target
  m_hCursorSelect = LoadCursor(NULL, IDC_HAND);  // Grab/select
  m_hCursorProhibit = LoadCursor(NULL, IDC_NO);  // Prohibited
  m_hCursorSpell =
      LoadCursor(NULL, IDC_CROSS); // Spell target (same as attack for now)

  // Set default cursor
  m_hCurrentCursor = m_hCursorNormal;
  ::SetCursor(m_hCurrentCursor);

  m_bEnabled = true;

  OutputDebugStringA("Hardware Cursor: Initialized\n");
}

void CHardwareCursor::Cleanup() {
  // Standard system cursors loaded with LoadCursor(NULL, ...) don't need to be
  // destroyed Only cursors created with CreateIcon...() need destruction
  m_hCurrentCursor = nullptr;
  m_hCursorNormal = nullptr;
  m_hCursorAttack = nullptr;
  m_hCursorSelect = nullptr;
  m_hCursorProhibit = nullptr;
  m_hCursorSpell = nullptr;

  m_bEnabled = false;
}

void CHardwareCursor::SetCursor(int frameIndex) {
  if (!m_bEnabled) {
    return;
  }

  HCURSOR hNewCursor = MapFrameToCursor(frameIndex);

  if (hNewCursor && hNewCursor != m_hCurrentCursor) {
    m_hCurrentCursor = hNewCursor;
    ::SetCursor(hNewCursor);
  }
}

void CHardwareCursor::SetEnabled(bool enabled) {
  m_bEnabled = enabled;

  if (enabled) {
    // Show hardware cursor
    ::ShowCursor(TRUE);
    if (m_hCurrentCursor) {
      ::SetCursor(m_hCurrentCursor);
    }
  } else {
    // Hide hardware cursor (for drag & drop with software cursor)
    ::ShowCursor(FALSE);
  }
}

HCURSOR CHardwareCursor::MapFrameToCursor(int frameIndex) {
  switch (frameIndex) {
  case CURSOR_NORMAL: // 0
    return m_hCursorNormal;

  case CURSOR_ATTACK_RED: // 3
  case CURSOR_ATTACK_ALT: // 6
    return m_hCursorAttack;

  case CURSOR_SPELL_BLUE:  // 4
  case CURSOR_SPELL_BLUE2: // 5
    return m_hCursorSpell;

  case CURSOR_PROHIBIT: // 8
    return m_hCursorProhibit;

  case CURSOR_GRAB: // 10
    return m_hCursorSelect;

  default:
    // Unknown frame, use normal cursor
    return m_hCursorNormal;
  }
}

HCURSOR CHardwareCursor::CreateCursorForFrame(int frameIndex) {
  // TODO: Advanced implementation
  // This would load the actual cursor sprite from the game's sprite file
  // and convert it to an HCURSOR using CreateIconIndirect()
  // For now, we use system cursors which work perfectly well
  return MapFrameToCursor(frameIndex);
}
