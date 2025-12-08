$path = "d:\HB-Server-Apocalypse\Client\Client.vcxproj"
$txt = Get-Content $path -Raw

# Injects CPPs
if ($txt -notmatch "DX11Renderer.cpp") {
    $cppBlock = '    <ClCompile Include="Game.cpp" />'
    $newCpp = '    <ClCompile Include="Game.cpp" />' + "`r`n" + 
              '    <ClCompile Include="Renderer\DX11Renderer.cpp" />' + "`r`n" +
              '    <ClCompile Include="Renderer\SpriteBatcher.cpp" />' + "`r`n" +
              '    <ClCompile Include="Renderer\TextureManager.cpp" />'
    
    # Simple replace might fail if Game.cpp tag varies (e.g. whitespace)
    # Trying regex
    $txt = $txt -replace '<ClCompile\s+Include="Game\.cpp"\s*/>', $newCpp
}

# Injects Headers
if ($txt -notmatch "DX11Renderer.h") {
    $hBlock = '    <ClInclude Include="Game.h" />'
    $newH = '    <ClInclude Include="Game.h" />' + "`r`n" + 
            '    <ClInclude Include="Renderer\DX11Renderer.h" />' + "`r`n" +
            '    <ClInclude Include="Renderer\SpriteBatcher.h" />' + "`r`n" +
            '    <ClInclude Include="Renderer\TextureManager.h" />'
    
    $txt = $txt -replace '<ClInclude\s+Include="Game\.h"\s*/>', $newH
}

Set-Content $path $txt -NoNewline
Write-Output "Client.vcxproj Updated"
