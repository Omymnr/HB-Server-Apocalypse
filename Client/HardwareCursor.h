// HardwareCursor.h - Hardware Cursor Management for Helbreath
#pragma once
#include <windows.h>

// Cursor types matching original software cursor frames
enum CursorType {
  CURSOR_NORMAL = 0,      // Frame 0 - Default arrow
  CURSOR_ATTACK_RED = 3,  // Frame 3 - Attack enemy
  CURSOR_SPELL_BLUE = 4,  // Frame 4 - Spell friendly
  CURSOR_SPELL_BLUE2 = 5, // Frame 5 - Another spell friendly
  CURSOR_ATTACK_ALT = 6,  // Frame 6 - Alternative attack
  CURSOR_PROHIBIT = 8,    // Frame 8 - Prohibited action
  CURSOR_GRAB = 10,       // Frame 10 - Grab/select item
  CURSOR_BORDER = 12      // Frame 12 - Border (decorative - not used as cursor)
};

class CHardwareCursor {
private:
  bool m_bEnabled;
  HCURSOR m_hCurrentCursor;

  // Cursor handles for different states
  HCURSOR m_hCursorNormal;
  HCURSOR m_hCursorAttack;
  HCURSOR m_hCursorSelect;
  HCURSOR m_hCursorProhibit;
  HCURSOR m_hCursorSpell;

public:
  CHardwareCursor();
  ~CHardwareCursor();

  void Initialize();
  void Cleanup();

  void SetCursor(int frameIndex);
  void SetEnabled(bool enabled);
  bool IsEnabled() const { return m_bEnabled; }

private:
  HCURSOR CreateCursorForFrame(int frameIndex);
  HCURSOR MapFrameToCursor(int frameIndex);
};

// Global hardware cursor instance
extern CHardwareCursor *g_pHardwareCursor;
