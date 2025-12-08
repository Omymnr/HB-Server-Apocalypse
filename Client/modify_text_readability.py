import re
import sys

def modify_game_cpp():
    """Modify Game.cpp to improve text readability"""
    
    file_path = r"d:\HB-Server-Apocalypse\Client\Game.cpp"
    
    # Try different encodings
    encodings = ['latin-1', 'cp1252', 'iso-8859-1', 'utf-8']
    content = None
    used_encoding = None
    
    for enc in encodings:
        try:
            with open(file_path, 'r', encoding=enc, errors='replace') as f:
                content = f.read()
            used_encoding = enc
            print(f"Successfully read with encoding: {enc}")
            break
        except:
            continue
    
    if content is None:
        print("ERROR: Could not read file")
        return False
    
    # Modification 1: PutString_SprFont - Fix shadow (line ~7935)
    old_shadow = 'm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos+1, iY, cTmpStr[iCnt] - 33, sR+11, sG+7, sB+6, dwTime);'
    new_shadow = '''// Triple black shadow for maximum contrast
\t\t\tm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos+1, iY,   cTmpStr[iCnt] - 33, 0, 0, 0, dwTime);
\t\t\tm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos,   iY+1, cTmpStr[iCnt] - 33, 0, 0, 0, dwTime);
\t\t\tm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos+1, iY+1, cTmpStr[iCnt] - 33, 0, 0, 0, dwTime);'''
    
    if old_shadow in content:
        content = content.replace(old_shadow, new_shadow)
        print("✓ Fixed shadow in PutString_SprFont (black instead of colored)")
    else:
        print("✗ Shadow pattern not found in PutString_SprFont")
    
    # Modification 2: PutString_SprFont - Force white for bright text
    old_text_render = '\t\t\telse m_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, sR, sG, sB, dwTime);'
    new_text_render = '''\t\t\telse {
\t\t\t\t// Force pure white for bright text (maximum visibility)
\t\t\t\tbool isWhiteText = (sR + sG + sB > 600);  // Sum > 600 = bright/white
\t\t\t\tif (isWhiteText)
\t\t\t\t\tm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, 255, 255, 255, dwTime);
\t\t\t\telse
\t\t\t\t\tm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, sR, sG, sB, dwTime);
\t\t\t}'''
    
    # Find in PutString_SprFont context (after the shadow fix)
    lines = content.split('\n')
    for i, line in enumerate(lines):
        if 'void CGame::PutString_SprFont(int iX, int iY' in line:
            # Look for the else statement in next 30 lines
            for j in range(i, min(i+30, len(lines))):
                if 'else m_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, sR, sG, sB, dwTime);' in lines[j]:
                    lines[j] = new_text_render
                    print(f"✓ Added white boost to PutString_SprFont at line {j}")
                    break
            break
    
    content = '\n'.join(lines)
    
    # Modification 3: PutString_SprFont2 - Force white for bright text
    old_text_render2 = '\t\t\telse m_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, iR, iG, iB, dwTime);'
    new_text_render2 = '''\t\t\telse {
\t\t\t\t// Force pure white for bright text (maximum visibility)
\t\t\t\tbool isWhiteText = (iR + iG + iB > 600);  // Sum > 600 = bright/white
\t\t\t\tif (isWhiteText)
\t\t\t\t\tm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, 255, 255, 255, dwTime);
\t\t\t\telse
\t\t\t\t\tm_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, iR, iG, iB, dwTime);
\t\t\t}'''
    
    lines = content.split('\n')
    for i, line in enumerate(lines):
        if 'void CGame::PutString_SprFont2(int iX, int iY' in line:
            # Look for the else statement in next 30 lines
            for j in range(i, min(i+30, len(lines))):
                if 'else m_pSprite[DEF_SPRID_INTERFACE_FONT1]->PutSpriteRGB(iXpos, iY, cTmpStr[iCnt] - 33, iR, iG, iB, dwTime);' in lines[j]:
                    lines[j] = new_text_render2
                    print(f"✓ Added white boost to PutString_SprFont2 at line {j}")
                    break
            break
    
    content = '\n'.join(lines)
    
    # Write back
    try:
        with open(file_path, 'w', encoding=used_encoding) as f:
            f.write(content)
        print(f"\nSuccessfully modified Game.cpp")
        return True
    except Exception as e:
        print(f"ERROR writing file: {e}")
        return False

if __name__ == "__main__":
    success = modify_game_cpp()
    sys.exit(0 if success else 1)
