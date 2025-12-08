#include "DX11Renderer.h"
#include "SpriteBatcher.h"
#include "TextureManager.h"
#include <stdio.h>

// Global Renderer Access for Legacy Classes (Sprite)
DX11Renderer *g_pRenderer = nullptr;

DX11Renderer::DX11Renderer()
    : m_vsyncEnabled(false), m_screenWidth(0), m_screenHeight(0), m_hwnd(0) {
  m_textureManager = nullptr;
  m_spriteBatcher = nullptr;
}

DX11Renderer::~DX11Renderer() { Cleanup(); }

void DX11Renderer::Cleanup() {
  if (m_spriteBatcher) {
    delete m_spriteBatcher;
    m_spriteBatcher = nullptr;
  }
  if (m_textureManager) {
    delete m_textureManager;
    m_textureManager = nullptr;
  }
  if (m_context) {
    m_context->ClearState();
  }
  if (m_swapChain)
    m_swapChain->SetFullscreenState(false, NULL);
}

bool DX11Renderer::Initialize(HWND hWnd, int screenWidth, int screenHeight,
                              bool vsync) {
  g_pRenderer = this; // Self-Assign Global Accessor

  m_hwnd = hWnd;
  m_screenWidth = screenWidth;
  m_screenHeight = screenHeight;
  m_vsyncEnabled = vsync;

  if (!CreateDeviceAndSwapChain(hWnd, screenWidth, screenHeight)) {
    return false;
  }

  OnResize(screenWidth, screenHeight);

  if (!CreateBlendStates()) {
    return false;
  }

  if (!CreateRasterizerState()) {
    return false;
  }

  // Initialize Sub-Systems
  m_textureManager = new TextureManager(this);
  m_spriteBatcher = new SpriteBatcher(this);
  if (!m_spriteBatcher->Initialize())
    return false;

  return true;
}

bool DX11Renderer::CreateDeviceAndSwapChain(HWND hWnd, int width, int height) {
  DXGI_SWAP_CHAIN_DESC swapChainDesc;
  ZeroMemory(&swapChainDesc, sizeof(swapChainDesc));

  swapChainDesc.BufferCount = 1;
  swapChainDesc.BufferDesc.Width = width;
  swapChainDesc.BufferDesc.Height = height;
  swapChainDesc.BufferDesc.Format = DXGI_FORMAT_R8G8B8A8_UNORM;

  // Refresh rate
  if (m_vsyncEnabled) {
    swapChainDesc.BufferDesc.RefreshRate.Numerator = 60;
    swapChainDesc.BufferDesc.RefreshRate.Denominator = 1;
  } else {
    swapChainDesc.BufferDesc.RefreshRate.Numerator = 0;
    swapChainDesc.BufferDesc.RefreshRate.Denominator = 1;
  }

  swapChainDesc.BufferUsage = DXGI_USAGE_RENDER_TARGET_OUTPUT;
  swapChainDesc.OutputWindow = hWnd;
  swapChainDesc.SampleDesc.Count = 1;
  swapChainDesc.SampleDesc.Quality = 0;
  swapChainDesc.Windowed = TRUE;
  swapChainDesc.BufferDesc.ScanlineOrdering =
      DXGI_MODE_SCANLINE_ORDER_UNSPECIFIED;
  swapChainDesc.BufferDesc.Scaling = DXGI_MODE_SCALING_UNSPECIFIED;
  swapChainDesc.SwapEffect = DXGI_SWAP_EFFECT_DISCARD;
  swapChainDesc.Flags = 0;

  UINT creationFlags = 0;
#ifdef _DEBUG
  creationFlags |= D3D11_CREATE_DEVICE_DEBUG;
#endif

  D3D_FEATURE_LEVEL featureLevels[] = {
      D3D_FEATURE_LEVEL_11_0,
      D3D_FEATURE_LEVEL_10_1,
      D3D_FEATURE_LEVEL_10_0,
  };
  UINT numFeatureLevels = ARRAYSIZE(featureLevels);
  D3D_FEATURE_LEVEL featureLevel;

  HRESULT result = D3D11CreateDeviceAndSwapChain(
      NULL, D3D_DRIVER_TYPE_HARDWARE, NULL, creationFlags, featureLevels,
      numFeatureLevels, D3D11_SDK_VERSION, &swapChainDesc,
      m_swapChain.GetAddressOf(), m_device.GetAddressOf(), &featureLevel,
      m_context.GetAddressOf());

  if (FAILED(result)) {
    return false;
  }

  return true;
}

bool DX11Renderer::CreateRenderTarget() {
  ID3D11Texture2D *backBufferPtr;
  HRESULT result = m_swapChain->GetBuffer(0, __uuidof(ID3D11Texture2D),
                                          (LPVOID *)&backBufferPtr);
  if (FAILED(result)) {
    return false;
  }

  result = m_device->CreateRenderTargetView(backBufferPtr, NULL,
                                            m_renderTargetView.GetAddressOf());
  backBufferPtr->Release();

  if (FAILED(result)) {
    return false;
  }

  return true;
}

bool DX11Renderer::CreateDepthStencil() { return true; }

bool DX11Renderer::CreateRasterizerState() {
  D3D11_RASTERIZER_DESC rasterDesc;
  ZeroMemory(&rasterDesc, sizeof(rasterDesc));

  rasterDesc.AntialiasedLineEnable = false;
  rasterDesc.CullMode = D3D11_CULL_NONE;
  rasterDesc.DepthBias = 0;
  rasterDesc.DepthBiasClamp = 0.0f;
  rasterDesc.DepthClipEnable = true;
  rasterDesc.FillMode = D3D11_FILL_SOLID;
  rasterDesc.FrontCounterClockwise = false;
  rasterDesc.MultisampleEnable = false;
  rasterDesc.ScissorEnable = false;
  rasterDesc.SlopeScaledDepthBias = 0.0f;

  HRESULT result = m_device->CreateRasterizerState(
      &rasterDesc, m_rasterizerState.GetAddressOf());
  if (FAILED(result)) {
    return false;
  }

  m_context->RSSetState(m_rasterizerState.Get());
  return true;
}

bool DX11Renderer::CreateBlendStates() {
  D3D11_BLEND_DESC blendDesc;
  ZeroMemory(&blendDesc, sizeof(blendDesc));

  // Create OPAQUE Blend State (no blending - for backbuffer and opaque sprites)
  blendDesc.RenderTarget[0].BlendEnable = FALSE; // CRITICAL: Disable blending
  blendDesc.RenderTarget[0].RenderTargetWriteMask = 0x0f;

  HRESULT result =
      m_device->CreateBlendState(&blendDesc, m_blendStateOpaque.GetAddressOf());
  if (FAILED(result)) {
    return false;
  }

  // Create ALPHA Blend State (standard alpha blending)
  blendDesc.RenderTarget[0].BlendEnable = TRUE;
  blendDesc.RenderTarget[0].SrcBlend = D3D11_BLEND_SRC_ALPHA;
  blendDesc.RenderTarget[0].DestBlend = D3D11_BLEND_INV_SRC_ALPHA;
  blendDesc.RenderTarget[0].BlendOp = D3D11_BLEND_OP_ADD;
  blendDesc.RenderTarget[0].SrcBlendAlpha = D3D11_BLEND_ONE;
  blendDesc.RenderTarget[0].DestBlendAlpha = D3D11_BLEND_ZERO;
  blendDesc.RenderTarget[0].BlendOpAlpha = D3D11_BLEND_OP_ADD;
  blendDesc.RenderTarget[0].RenderTargetWriteMask = 0x0f;

  result =
      m_device->CreateBlendState(&blendDesc, m_blendStateAlpha.GetAddressOf());
  if (FAILED(result)) {
    return false;
  }

  // Create Additive Blend State
  blendDesc.RenderTarget[0].BlendEnable = TRUE;
  blendDesc.RenderTarget[0].SrcBlend = D3D11_BLEND_SRC_ALPHA;
  blendDesc.RenderTarget[0].DestBlend = D3D11_BLEND_ONE;
  blendDesc.RenderTarget[0].BlendOp = D3D11_BLEND_OP_ADD;
  blendDesc.RenderTarget[0].SrcBlendAlpha = D3D11_BLEND_ONE;
  blendDesc.RenderTarget[0].DestBlendAlpha = D3D11_BLEND_ZERO;
  blendDesc.RenderTarget[0].BlendOpAlpha = D3D11_BLEND_OP_ADD;
  blendDesc.RenderTarget[0].RenderTargetWriteMask = 0x0f;

  result = m_device->CreateBlendState(&blendDesc,
                                      m_blendStateAdditive.GetAddressOf());
  if (FAILED(result)) {
    return false;
  }

  // Set default blend state to OPAQUE (for backbuffer rendering)
  float blendFactor[4] = {0.0f, 0.0f, 0.0f, 0.0f};
  m_context->OMSetBlendState(m_blendStateOpaque.Get(), blendFactor, 0xffffffff);

  return true;
}

void DX11Renderer::OnResize(int width, int height) {
  m_screenWidth = width;
  m_screenHeight = height;

  if (m_context) {
    m_context->OMSetRenderTargets(0, 0, 0);
    m_renderTargetView.Reset();
  }

  if (m_swapChain) {
    m_swapChain->ResizeBuffers(0, width, height, DXGI_FORMAT_UNKNOWN, 0);
  }

  CreateRenderTarget();

  // Setup viewport
  m_viewport.Width = (float)width;
  m_viewport.Height = (float)height;
  m_viewport.MinDepth = 0.0f;
  m_viewport.MaxDepth = 1.0f;
  m_viewport.TopLeftX = 0.0f;
  m_viewport.TopLeftY = 0.0f;

  if (m_context) {
    m_context->RSSetViewports(1, &m_viewport);
    m_context->OMSetRenderTargets(1, m_renderTargetView.GetAddressOf(), NULL);
  }
}

void DX11Renderer::BeginFrame(float r, float g, float b, float a) {
  float color[4];
  color[0] = r;
  color[1] = g;
  color[2] = b;
  color[3] = a;

  m_context->ClearRenderTargetView(m_renderTargetView.Get(), color);
}

void DX11Renderer::EndFrame() {
  if (m_vsyncEnabled) {
    m_swapChain->Present(1, 0);
  } else {
    m_swapChain->Present(0, 0);
  }
}
