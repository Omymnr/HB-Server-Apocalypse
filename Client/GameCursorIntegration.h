// GameCursorIntegration.h - Integration glue for hardware cursor
#pragma once

// Initialize hardware cursor system (call from Game::bInit)
void InitializeGameHardwareCursor();

// Cleanup hardware cursor system (call from Game destructor)
void CleanupGameHardwareCursor();

// Update cursor based on game state
// cursorFrame: the sprite frame that would be rendered (0-12)
// isDragging: true if player is dragging an item
// Returns: true if hardware cursor is handling it, false if software cursor
// should render
bool UpdateGameHardwareCursor(int cursorFrame, bool isDragging);
