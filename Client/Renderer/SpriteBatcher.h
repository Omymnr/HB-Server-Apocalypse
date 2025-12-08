#pragma once

#include "DX11Renderer.h"
#include <algorithm> // For std::sort
#include <d3d11.h>
#include <directxmath.h>
#include <memory>
#include <vector>
#include <wrl/client.h>

using namespace DirectX;
using namespace Microsoft::WRL;

struct SpriteVertex {
  XMFLOAT3 position;
  XMFLOAT2 tex;
  XMFLOAT4 color;
};

class SpriteBatcher {
public:
  SpriteBatcher(DX11Renderer *renderer);
  ~SpriteBatcher();

  bool Initialize();
  void Begin();
  void End();

  // New Draw using RenderCommand struct with BlendMode support
  void Draw(ID3D11ShaderResourceView *texture, float x, float y, float width,
            float height, float r = 1.0f, float g = 1.0f, float b = 1.0f,
            float a = 1.0f,
            DX11Renderer::BlendMode blendMode = DX11Renderer::BLEND_OPAQUE);
  void DrawRect(ID3D11ShaderResourceView *texture, const RECT &sourceRect,
                float texWidth, float texHeight, float x, float y, float width,
                float height, float r = 1.0f, float g = 1.0f, float b = 1.0f,
                float a = 1.0f,
                DX11Renderer::BlendMode blendMode = DX11Renderer::BLEND_OPAQUE);

private:
  bool InitializeShader();
  bool InitializeBuffers();

  // Deferred Rendering Logic
  void SortAndRender();
  void FlushBatch();

  struct MatrixBufferType {
    XMMATRIX world;
    XMMATRIX view;
    XMMATRIX projection;
  };

  // Internal Command Struct
  struct RenderCommand {
    ID3D11ShaderResourceView *texture;
    RECT srcRect;
    float x, y, w, h;
    float texWidth, texHeight; // For UV calculation from atlas
    float r, g, b, a;
    float depth; // For Sorting (Base Y coordinate)
    DX11Renderer::BlendMode blendMode;
  };

private:
  DX11Renderer *m_renderer;

  // DX11 Resources
  ComPtr<ID3D11VertexShader> m_vertexShader;
  ComPtr<ID3D11PixelShader> m_pixelShader;
  ComPtr<ID3D11InputLayout> m_layout;
  ComPtr<ID3D11Buffer> m_matrixBuffer;
  ComPtr<ID3D11SamplerState> m_sampleState;

  // States
  ComPtr<ID3D11RasterizerState> m_rasterState;
  ComPtr<ID3D11BlendState> m_blendState;

  // Batching
  static const int MAX_BATCH_SIZE = 8192; // Increased size
  ComPtr<ID3D11Buffer> m_vertexBuffer;
  ComPtr<ID3D11Buffer> m_indexBuffer;

  // Queues
  std::vector<RenderCommand> m_commandQueue;
  std::vector<SpriteVertex> m_batchVertices;
  ID3D11ShaderResourceView *m_currentTexture;
};
