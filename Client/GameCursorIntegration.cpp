// GameCursorIntegration.cpp - Integration glue for hardware cursor
// This file bridges the hardware cursor system with the game's existing cursor
// logic

#include "Game.h"
#include "HardwareCursor.h"
#include "SpriteID.h"

// Initialize hardware cursor system (call from Game::bInit)
void InitializeGameHardwareCursor() {
  if (g_pHardwareCursor == nullptr) {
    g_pHardwareCursor = new CHardwareCursor();
    g_pHardwareCursor->Initialize();
    OutputDebugStringA("Game: Hardware cursor system initialized\n");
  }
}

// Cleanup hardware cursor system (call from Game destructor)
void CleanupGameHardwareCursor() {
  if (g_pHardwareCursor) {
    g_pHardwareCursor->Cleanup();
    delete g_pHardwareCursor;
    g_pHardwareCursor = nullptr;
    OutputDebugStringA("Game: Hardware cursor system cleaned up\n");
  }
}

// Update cursor based on game state (call this instead of PutSpriteFast for
// cursor) Returns true if hardware cursor handled it, false if software cursor
// should be used
bool UpdateGameHardwareCursor(int cursorFrame, bool isDragging) {
  if (!g_pHardwareCursor || !g_pHardwareCursor->IsEnabled()) {
    return false; // Hardware cursor not active
  }

  if (isDragging) {
    // During drag & drop, disable hardware cursor and use software cursor
    g_pHardwareCursor->SetEnabled(false);
    return false; // Software cursor will render
  } else {
    // Normal operation - use hardware cursor
    g_pHardwareCursor->SetEnabled(true);
    g_pHardwareCursor->SetCursor(cursorFrame);
    return true; // Hardware cursor active, don't render software cursor
  }
}
