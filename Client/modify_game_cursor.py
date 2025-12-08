import sys
import os

def add_hardware_cursor_calls():
    """Modify Game.cpp to add hardware cursor initialization and cleanup"""
    
    game_cpp_path = r"d:\HB-Server-Apocalypse\Client\Game.cpp"
    
    if not os.path.exists(game_cpp_path):
        print(f"ERROR: {game_cpp_path} not found")
        return False
    
    # Read the file with explicit encoding handling
    encodings = ['utf-8', 'latin-1', 'cp1252', 'iso-8859-1']
    content = None
    used_encoding = None
    
    for encoding in encodings:
        try:
            with open(game_cpp_path, 'r', encoding=encoding, errors='replace') as f:
                content = f.read()
            used_encoding = encoding
            print(f"Successfully read with encoding: {encoding}")
            break
        except Exception as e:
            print(f"Failed with {encoding}: {e}")
            continue
    
    if content is None:
        print("ERROR: Could not read file with any encoding")
        return False
    
    # 1. Add includes near the top (after existing includes)
    include_marker = '#include "Game.h"'
    if include_marker in content:
        new_includes = '''#include "Game.h"
#include "GameCursorIntegration.h"
#include "GameCursorMacro.h"'''
        content = content.replace('#include "Game.h"', new_includes, 1)
        print("Added hardware cursor includes")
    
    # 2. Add initialization call in bInit function
    # Look for a good place - after DX11Renderer initialization would be ideal
    # But let's just add it near the end before "return true"
    init_marker = 'bool CGame::bInit(HWND hWnd, HINSTANCE hInst, char * pCmdLine)'
    if init_marker in content:
        # Find the function and add init call before first "return true"
        # This is a bit crude but should work
        init_call = '\n\t// Initialize hardware cursor system\n\tInitializeGameHardwareCursor();\n\n\treturn true;'
        # Find the bInit function's first "return true"
        start_pos = content.find(init_marker)
        if start_pos != -1:
            # Find first "return true" after the function start
            search_start = start_pos + len(init_marker)
            return_pos = content.find('\treturn true;', search_start)
            if return_pos != -1:
                # Replace this return with our init call
                content = content[:return_pos] + init_call + content[return_pos + len('\treturn true;'):]
                print("Added InitializeGameHardwareCursor() call in bInit()")
    
    # 3. Add cleanup call in destructor
    destructor_marker = 'CGame::~CGame()'
    if destructor_marker in content:
        # Add cleanup at the start of the destructor
        cleanup_call = '\n{\n\t// Cleanup hardware cursor system\n\tCleanupGameHardwareCursor();\n\n'
        # Find the destructor opening brace
        start_pos = content.find(destructor_marker)
        if start_pos != -1:
            brace_pos = content.find('{', start_pos)
            if brace_pos != -1:
                content = content[:brace_pos] + cleanup_call + content[brace_pos+2:]  # +2 to skip "{\n"
                print("Added CleanupGameHardwareCursor() call in destructor")
    
    # Write back
    try:
        with open(game_cpp_path, 'w', encoding=used_encoding) as f:
            f.write(content)
        print(f"Successfully wrote modifications to Game.cpp")
        return True
    except Exception as e:
        print(f"ERROR writing file: {e}")
        return False

if __name__ == "__main__":
    success = add_hardware_cursor_calls()
    sys.exit(0 if success else 1)
