#include "SpriteBatcher.h"
#include <algorithm>
#include <d3dcompiler.h>

// extern void LogDebug(const char *fmt, ...);

SpriteBatcher::SpriteBatcher(DX11Renderer *renderer)
    : m_renderer(renderer), m_currentTexture(nullptr) {
  // LogDebug("SB: Constructor. This=%p, Renderer=%p", this, renderer);
  m_commandQueue.reserve(MAX_BATCH_SIZE);
  m_batchVertices.reserve(MAX_BATCH_SIZE * 4);
}

SpriteBatcher::~SpriteBatcher() {
  // LogDebug("SB: Destructor. This=%p", this);
}

bool SpriteBatcher::Initialize() {
  if (!InitializeShader())
    return false;

  if (!InitializeBuffers())
    return false;

  return true;
}

bool SpriteBatcher::InitializeShader() {
  ID3D10Blob *errorMessage = nullptr;
  ID3D10Blob *vertexShaderBuffer = nullptr;
  ID3D10Blob *pixelShaderBuffer = nullptr;

  // Compile Vertex Shader
  HRESULT result = D3DCompileFromFile(
      L"Renderer/Shaders.hlsl", NULL, NULL, "TextureVertexShader", "vs_5_0",
      D3D10_SHADER_ENABLE_STRICTNESS, 0, &vertexShaderBuffer, &errorMessage);
  if (FAILED(result)) {
    if (errorMessage) {
      // LogDebug("SB ERROR: VS Compile Failed: %s",
      //          (char *)errorMessage->GetBufferPointer());
      errorMessage->Release();
    } else {
      // LogDebug("SB ERROR: VS Compile Failed (No Error Message). HRESULT=%x",
      //          result);
    }
    return false;
  }

  // Compile Pixel Shader
  result = D3DCompileFromFile(
      L"Renderer/Shaders.hlsl", NULL, NULL, "TexturePixelShader", "ps_5_0",
      D3D10_SHADER_ENABLE_STRICTNESS, 0, &pixelShaderBuffer, &errorMessage);
  if (FAILED(result)) {
    if (errorMessage) {
      // LogDebug("SB ERROR: PS Compile Failed: %s",
      //          (char *)errorMessage->GetBufferPointer());
      errorMessage->Release();
    } else {
      // LogDebug("SB ERROR: PS Compile Failed (No Error Message). HRESULT=%x",
      //          result);
    }
    return false;
  }

  // Create Shaders
  result = m_renderer->GetDevice()->CreateVertexShader(
      vertexShaderBuffer->GetBufferPointer(),
      vertexShaderBuffer->GetBufferSize(), NULL, &m_vertexShader);
  if (FAILED(result)) {
    // LogDebug("SB ERROR: CreateVertexShader Failed %x", result);
    return false;
  }

  result = m_renderer->GetDevice()->CreatePixelShader(
      pixelShaderBuffer->GetBufferPointer(), pixelShaderBuffer->GetBufferSize(),
      NULL, &m_pixelShader);
  if (FAILED(result)) {
    // LogDebug("SB ERROR: CreatePixelShader Failed %x", result);
    return false;
  }

  // Create Input Layout
  D3D11_INPUT_ELEMENT_DESC polygonLayout[3];
  polygonLayout[0].SemanticName = "POSITION";
  polygonLayout[0].SemanticIndex = 0;
  polygonLayout[0].Format = DXGI_FORMAT_R32G32B32_FLOAT;
  polygonLayout[0].InputSlot = 0;
  polygonLayout[0].AlignedByteOffset = 0;
  polygonLayout[0].InputSlotClass = D3D11_INPUT_PER_VERTEX_DATA;
  polygonLayout[0].InstanceDataStepRate = 0;

  polygonLayout[1].SemanticName = "TEXCOORD";
  polygonLayout[1].SemanticIndex = 0;
  polygonLayout[1].Format = DXGI_FORMAT_R32G32_FLOAT;
  polygonLayout[1].InputSlot = 0;
  polygonLayout[1].AlignedByteOffset = D3D11_APPEND_ALIGNED_ELEMENT;
  polygonLayout[1].InputSlotClass = D3D11_INPUT_PER_VERTEX_DATA;
  polygonLayout[1].InstanceDataStepRate = 0;

  polygonLayout[2].SemanticName = "COLOR";
  polygonLayout[2].SemanticIndex = 0;
  polygonLayout[2].Format = DXGI_FORMAT_R32G32B32A32_FLOAT;
  polygonLayout[2].InputSlot = 0;
  polygonLayout[2].AlignedByteOffset = D3D11_APPEND_ALIGNED_ELEMENT;
  polygonLayout[2].InputSlotClass = D3D11_INPUT_PER_VERTEX_DATA;
  polygonLayout[2].InstanceDataStepRate = 0;

  result = m_renderer->GetDevice()->CreateInputLayout(
      polygonLayout, 3, vertexShaderBuffer->GetBufferPointer(),
      vertexShaderBuffer->GetBufferSize(), &m_layout);
  vertexShaderBuffer->Release();
  pixelShaderBuffer->Release();
  if (FAILED(result)) {
    // LogDebug("SB ERROR: CreateInputLayout Failed %x", result);
    return false;
  }

  // Create Constant Buffer (Matrix)
  D3D11_BUFFER_DESC matrixBufferDesc;
  matrixBufferDesc.Usage = D3D11_USAGE_DYNAMIC;
  matrixBufferDesc.ByteWidth = sizeof(MatrixBufferType);
  matrixBufferDesc.BindFlags = D3D11_BIND_CONSTANT_BUFFER;
  matrixBufferDesc.CPUAccessFlags = D3D11_CPU_ACCESS_WRITE;
  matrixBufferDesc.MiscFlags = 0;
  matrixBufferDesc.StructureByteStride = 0;

  result = m_renderer->GetDevice()->CreateBuffer(&matrixBufferDesc, NULL,
                                                 &m_matrixBuffer);
  if (FAILED(result)) {
    // LogDebug("SB ERROR: Create Matrix Buffer Failed %x", result);
    return false;
  }

  // Create Sampler State (Linear Filtering)
  D3D11_SAMPLER_DESC samplerDesc;
  samplerDesc.Filter = D3D11_FILTER_MIN_MAG_MIP_LINEAR;
  samplerDesc.AddressU = D3D11_TEXTURE_ADDRESS_WRAP;
  samplerDesc.AddressV = D3D11_TEXTURE_ADDRESS_WRAP;
  samplerDesc.AddressW = D3D11_TEXTURE_ADDRESS_WRAP;
  samplerDesc.MipLODBias = 0.0f;
  samplerDesc.MaxAnisotropy = 1;
  samplerDesc.ComparisonFunc = D3D11_COMPARISON_ALWAYS;
  samplerDesc.BorderColor[0] = 0;
  samplerDesc.BorderColor[1] = 0;
  samplerDesc.BorderColor[2] = 0;
  samplerDesc.BorderColor[3] = 0;
  samplerDesc.MinLOD = 0;
  samplerDesc.MaxLOD = D3D11_FLOAT32_MAX;

  result =
      m_renderer->GetDevice()->CreateSamplerState(&samplerDesc, &m_sampleState);
  if (FAILED(result)) {
    // LogDebug("SB ERROR: CreateSamplerState Failed %x", result);
    return false;
  }

  // Create Rasterizer State (No Culling)
  D3D11_RASTERIZER_DESC rasterDesc;
  ZeroMemory(&rasterDesc, sizeof(rasterDesc));
  rasterDesc.AntialiasedLineEnable = false;
  rasterDesc.CullMode = D3D11_CULL_NONE;
  rasterDesc.FillMode = D3D11_FILL_SOLID;
  rasterDesc.DepthClipEnable = true;

  result = m_renderer->GetDevice()->CreateRasterizerState(&rasterDesc,
                                                          &m_rasterState);
  if (FAILED(result)) {
    // LogDebug("SB ERROR: CreateRasterizerState Failed %x", result);
    return false;
  }

  // Create Blend State (Alpha Blending)
  D3D11_BLEND_DESC blendDesc;
  ZeroMemory(&blendDesc, sizeof(blendDesc));
  blendDesc.RenderTarget[0].BlendEnable = TRUE;
  blendDesc.RenderTarget[0].SrcBlend = D3D11_BLEND_SRC_ALPHA;
  blendDesc.RenderTarget[0].DestBlend = D3D11_BLEND_INV_SRC_ALPHA;
  blendDesc.RenderTarget[0].BlendOp = D3D11_BLEND_OP_ADD;
  blendDesc.RenderTarget[0].SrcBlendAlpha = D3D11_BLEND_ONE;
  blendDesc.RenderTarget[0].DestBlendAlpha = D3D11_BLEND_ZERO;
  blendDesc.RenderTarget[0].BlendOpAlpha = D3D11_BLEND_OP_ADD;
  blendDesc.RenderTarget[0].RenderTargetWriteMask =
      D3D11_COLOR_WRITE_ENABLE_ALL;

  result = m_renderer->GetDevice()->CreateBlendState(&blendDesc, &m_blendState);
  if (FAILED(result)) {
    // LogDebug("SB ERROR: CreateBlendState Failed %x", result);
    return false;
  }

  return true;
}

bool SpriteBatcher::InitializeBuffers() {
  // Vertex Buffer (Dynamic)
  D3D11_BUFFER_DESC vertexBufferDesc;
  vertexBufferDesc.Usage = D3D11_USAGE_DYNAMIC;
  vertexBufferDesc.ByteWidth =
      sizeof(SpriteVertex) * MAX_BATCH_SIZE * 4; // 4 verts per sprite
  vertexBufferDesc.BindFlags = D3D11_BIND_VERTEX_BUFFER;
  vertexBufferDesc.CPUAccessFlags = D3D11_CPU_ACCESS_WRITE;
  vertexBufferDesc.MiscFlags = 0;
  vertexBufferDesc.StructureByteStride = 0;

  HRESULT result = m_renderer->GetDevice()->CreateBuffer(&vertexBufferDesc,
                                                         NULL, &m_vertexBuffer);
  if (FAILED(result)) {
    // LogDebug("SB ERROR: Create Vertex Buffer Failed %x", result);
    return false;
  }

  // Index Buffer (Static)
  // Correct Quad Topology: (0,1,2) TL-TR-BL, (1,3,2) TR-BR-BL
  unsigned long *indices = new unsigned long[MAX_BATCH_SIZE * 6];
  for (int i = 0; i < MAX_BATCH_SIZE; i++) {
    indices[i * 6 + 0] = i * 4 + 0; // TL
    indices[i * 6 + 1] = i * 4 + 1; // TR
    indices[i * 6 + 2] = i * 4 + 2; // BL

    indices[i * 6 + 3] = i * 4 + 1; // TR
    indices[i * 6 + 4] = i * 4 + 3; // BR
    indices[i * 6 + 5] = i * 4 + 2; // BL
  }

  D3D11_BUFFER_DESC indexBufferDesc;
  indexBufferDesc.Usage = D3D11_USAGE_DEFAULT;
  indexBufferDesc.ByteWidth = sizeof(unsigned long) * MAX_BATCH_SIZE * 6;
  indexBufferDesc.BindFlags = D3D11_BIND_INDEX_BUFFER;
  indexBufferDesc.CPUAccessFlags = 0;
  indexBufferDesc.MiscFlags = 0;
  indexBufferDesc.StructureByteStride = 0;

  D3D11_SUBRESOURCE_DATA indexData;
  indexData.pSysMem = indices;
  indexData.SysMemPitch = 0;
  indexData.SysMemSlicePitch = 0;

  result = m_renderer->GetDevice()->CreateBuffer(&indexBufferDesc, &indexData,
                                                 &m_indexBuffer);
  delete[] indices;
  if (FAILED(result)) {
    // LogDebug("SB ERROR: Create Index Buffer Failed %x", result);
    return false;
  }

  return true;
}

void SpriteBatcher::Begin() {
  // Clear the queues
  m_commandQueue.clear();
  m_batchVertices.clear();
  m_currentTexture = nullptr;
}

void SpriteBatcher::Draw(ID3D11ShaderResourceView *texture, float x, float y,
                         float width, float height, float r, float g, float b,
                         float a, DX11Renderer::BlendMode blendMode) {
  if (!texture)
    return;

  RenderCommand cmd;
  cmd.texture = texture;
  cmd.x = x;
  cmd.y = y;
  cmd.w = width;
  cmd.h = height;
  cmd.texWidth = 0.0f; // 0 = use full texture
  cmd.texHeight = 0.0f;
  cmd.r = r;
  cmd.g = g;
  cmd.b = b;
  cmd.a = a;
  cmd.srcRect = {0, 0, 0, 0};
  cmd.depth = y + height;
  cmd.blendMode = blendMode;

  m_commandQueue.push_back(cmd);
}

void SpriteBatcher::DrawRect(ID3D11ShaderResourceView *texture,
                             const RECT &sourceRect, float texWidth,
                             float texHeight, float x, float y, float width,
                             float height, float r, float g, float b, float a,
                             DX11Renderer::BlendMode blendMode) {
  if (!texture)
    return;

  RenderCommand cmd;
  cmd.texture = texture;
  cmd.x = x;
  cmd.y = y;
  cmd.w = width;
  cmd.h = height;
  cmd.texWidth = texWidth;
  cmd.texHeight = texHeight;
  cmd.r = r;
  cmd.g = g;
  cmd.b = b;
  cmd.a = a;
  cmd.srcRect = sourceRect;
  cmd.depth = y + height;
  cmd.blendMode = blendMode;

  m_commandQueue.push_back(cmd);
}

void SpriteBatcher::End() {
  // This is where we sort and render everything
  SortAndRender();
}

void SpriteBatcher::SortAndRender() {
  if (m_commandQueue.empty())
    return;

  // 1. Sort: Depth -> BlendMode -> Texture
  std::sort(m_commandQueue.begin(), m_commandQueue.end(),
            [](const RenderCommand &a, const RenderCommand &b) -> bool {
              if (a.depth < b.depth)
                return true;
              if (a.depth > b.depth)
                return false;
              // Sort by BlendMode to minimize state changes
              if (a.blendMode != b.blendMode)
                return a.blendMode < b.blendMode;
              // Then by Texture
              return a.texture < b.texture;
            });

  // 2. Setup DeviceContext
  ID3D11DeviceContext *context = m_renderer->GetContext();
  if (!context)
    return;

  context->IASetInputLayout(m_layout.Get());
  context->IASetPrimitiveTopology(D3D11_PRIMITIVE_TOPOLOGY_TRIANGLELIST);
  UINT stride = sizeof(SpriteVertex);
  UINT offset = 0;
  context->IASetVertexBuffers(0, 1, m_vertexBuffer.GetAddressOf(), &stride,
                              &offset);
  context->IASetIndexBuffer(m_indexBuffer.Get(), DXGI_FORMAT_R32_UINT, 0);
  context->VSSetShader(m_vertexShader.Get(), NULL, 0);
  context->PSSetShader(m_pixelShader.Get(), NULL, 0);
  context->PSSetSamplers(0, 1, m_sampleState.GetAddressOf());
  context->RSSetState(m_rasterState.Get());

  // Initial Blend State
  DX11Renderer::BlendMode currentBlendMode = DX11Renderer::BLEND_ALPHA;
  float blendFactor[4] = {0.0f, 0.0f, 0.0f, 0.0f};
  context->OMSetBlendState(m_renderer->GetBlendState(currentBlendMode),
                           blendFactor, 0xFFFFFFFF);

  // Update Matrix
  D3D11_VIEWPORT vp;
  UINT numVP = 1;
  context->RSGetViewports(&numVP, &vp);
  float L = 0, R = vp.Width, T = 0, B = vp.Height;
  XMMATRIX projection = XMMatrixOrthographicOffCenterLH(L, R, B, T, 0.0f, 1.0f);
  XMMATRIX world = XMMatrixIdentity();
  XMMATRIX view = XMMatrixIdentity();

  D3D11_MAPPED_SUBRESOURCE mappedResource;
  if (SUCCEEDED(context->Map(m_matrixBuffer.Get(), 0, D3D11_MAP_WRITE_DISCARD,
                             0, &mappedResource))) {
    MatrixBufferType *dataPtr = (MatrixBufferType *)mappedResource.pData;
    dataPtr->world = XMMatrixTranspose(world);
    dataPtr->view = XMMatrixTranspose(view);
    dataPtr->projection = XMMatrixTranspose(projection);
    context->Unmap(m_matrixBuffer.Get(), 0);
  }
  context->VSSetConstantBuffers(0, 1, m_matrixBuffer.GetAddressOf());

  // 3. Generate Batches with BlendMode switching
  m_batchVertices.clear();
  m_currentTexture = nullptr;

  for (const auto &cmd : m_commandQueue) {
    // Flush if texture, blendmode changes or batch full
    if ((m_currentTexture != nullptr && cmd.texture != m_currentTexture) ||
        (cmd.blendMode != currentBlendMode) ||
        m_batchVertices.size() >= MAX_BATCH_SIZE * 4) {
      FlushBatch();

      // Update Blend State if needed
      if (cmd.blendMode != currentBlendMode) {
        currentBlendMode = cmd.blendMode;
        context->OMSetBlendState(m_renderer->GetBlendState(currentBlendMode),
                                 blendFactor, 0xFFFFFFFF);
      }
    }

    m_currentTexture = cmd.texture;

    // Generate Quads
    float left = cmd.x;
    float right = cmd.x + cmd.w;
    float top = cmd.y;
    float bottom = cmd.y + cmd.h;

    // Calculate UVs from atlas
    float uv_left = 0.0f, uv_top = 0.0f, uv_right = 1.0f, uv_bottom = 1.0f;
    if (cmd.texWidth > 0.0f && cmd.texHeight > 0.0f) {
      uv_left = (float)cmd.srcRect.left / cmd.texWidth;
      uv_top = (float)cmd.srcRect.top / cmd.texHeight;
      uv_right = (float)cmd.srcRect.right / cmd.texWidth;
      uv_bottom = (float)cmd.srcRect.bottom / cmd.texHeight;
    }

    SpriteVertex v1, v2, v3, v4;
    v1.position = XMFLOAT3(left, top, 0.0f);
    v1.tex = XMFLOAT2(uv_left, uv_top);
    v1.color = XMFLOAT4(cmd.r, cmd.g, cmd.b, cmd.a);

    v2.position = XMFLOAT3(right, top, 0.0f);
    v2.tex = XMFLOAT2(uv_right, uv_top);
    v2.color = XMFLOAT4(cmd.r, cmd.g, cmd.b, cmd.a);

    v3.position = XMFLOAT3(left, bottom, 0.0f);
    v3.tex = XMFLOAT2(uv_left, uv_bottom);
    v3.color = XMFLOAT4(cmd.r, cmd.g, cmd.b, cmd.a);

    v4.position = XMFLOAT3(right, bottom, 0.0f);
    v4.tex = XMFLOAT2(uv_right, uv_bottom);
    v4.color = XMFLOAT4(cmd.r, cmd.g, cmd.b, cmd.a);

    m_batchVertices.push_back(v1);
    m_batchVertices.push_back(v2);
    m_batchVertices.push_back(v3);
    m_batchVertices.push_back(v4);
  }

  // Flush remaining
  FlushBatch();
}

void SpriteBatcher::FlushBatch() {
  if (m_batchVertices.empty() || !m_currentTexture)
    return;

  ID3D11DeviceContext *context = m_renderer->GetContext();

  D3D11_MAPPED_SUBRESOURCE mappedResource;
  if (SUCCEEDED(context->Map(m_vertexBuffer.Get(), 0, D3D11_MAP_WRITE_DISCARD,
                             0, &mappedResource))) {
    memcpy(mappedResource.pData, m_batchVertices.data(),
           sizeof(SpriteVertex) * m_batchVertices.size());
    context->Unmap(m_vertexBuffer.Get(), 0);
  }

  context->PSSetShaderResources(0, 1, &m_currentTexture);
  context->DrawIndexed((UINT)m_batchVertices.size() / 4 * 6, 0, 0);

  m_batchVertices.clear();
}
