#pragma once

#include <d3d11.h>
#include <d3dcompiler.h>
#include <directxmath.h>
#include <string>
#include <wrl/client.h>

#pragma comment(lib, "d3d11.lib")
#pragma comment(lib, "dxgi.lib")
#pragma comment(lib, "d3dcompiler.lib")

using namespace Microsoft::WRL;
using namespace DirectX;

class DX11Renderer {
public:
  DX11Renderer();
  ~DX11Renderer();

  // Initialization
  bool Initialize(HWND hWnd, int screenWidth, int screenHeight, bool vsync);
  void Cleanup();

  // Frame Lifecycle
  void BeginFrame(float r, float g, float b, float a);
  void EndFrame();

  // Device Access
  ID3D11Device *GetDevice() const { return m_device.Get(); }
  ID3D11DeviceContext *GetContext() const { return m_context.Get(); }

  // Window/Resize
  void OnResize(int width, int height);

private:
  bool CreateDeviceAndSwapChain(HWND hWnd, int width, int height);
  bool CreateRenderTarget();
  bool CreateDepthStencil();
  bool CreateRasterizerState();
  bool CreateBlendStates();

public:
  // Sub-Components
  class TextureManager *GetTextureManager() const { return m_textureManager; }
  class SpriteBatcher *GetSpriteBatcher() const { return m_spriteBatcher; }

private:
  // Core D3D11
  ComPtr<ID3D11Device> m_device;
  ComPtr<ID3D11DeviceContext> m_context;
  ComPtr<IDXGISwapChain> m_swapChain;
  ComPtr<ID3D11RenderTargetView> m_renderTargetView;

  // Depth/Stencil (Optional for pure 2D but good to have)
  ComPtr<ID3D11Texture2D> m_depthStencilBuffer;
  ComPtr<ID3D11DepthStencilView> m_depthStencilView;
  ComPtr<ID3D11DepthStencilState> m_depthStencilState;

  // States
  ComPtr<ID3D11RasterizerState> m_rasterizerState;
  ComPtr<ID3D11BlendState> m_blendStateAlpha;

  // Config
  bool m_vsyncEnabled;
  int m_videoCardMemory;
  char m_videoCardDescription[128];
  HWND m_hwnd;
  int m_screenWidth;
  int m_screenHeight;

  // Viewport
  D3D11_VIEWPORT m_viewport;

public:
  enum BlendMode { BLEND_OPAQUE, BLEND_ALPHA, BLEND_ADDITIVE };
  ID3D11BlendState *GetBlendState(BlendMode mode) {
    if (mode == BLEND_ADDITIVE)
      return m_blendStateAdditive.Get();
    if (mode == BLEND_OPAQUE)
      return m_blendStateOpaque.Get();
    return m_blendStateAlpha.Get();
  }

private:
  ComPtr<ID3D11BlendState> m_blendStateOpaque;
  ComPtr<ID3D11BlendState> m_blendStateAdditive;

  class TextureManager *m_textureManager;
  class SpriteBatcher *m_spriteBatcher;
};
