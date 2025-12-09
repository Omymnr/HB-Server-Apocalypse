Texture2D shaderTexture : register(t0);
SamplerState SampleType : register(s0);

cbuffer MatrixBuffer : register(b0)
{
    matrix worldMatrix;
    matrix viewMatrix;
    matrix projectionMatrix;
};

struct VertexInput
{
    float4 position : POSITION;
    float2 tex : TEXCOORD0;
    float4 color : COLOR;
};

struct PixelInput
{
    float4 position : SV_POSITION;
    float2 tex : TEXCOORD0;
    float4 color : COLOR;
};

// VERTEX SHADER
PixelInput TextureVertexShader(VertexInput input)
{
    PixelInput output;

    // Change the position vector to be 4 units for proper matrix calculations.
    input.position.w = 1.0f;

    // Calculate the position of the vertex against the world, view, and projection matrices.
    output.position = mul(input.position, worldMatrix);
    output.position = mul(output.position, viewMatrix);
    output.position = mul(output.position, projectionMatrix);

    // Store the texture coordinates for the pixel shader.
    output.tex = input.tex;
    
    // Pass the color through
    output.color = input.color;

    return output;
}

// PIXEL SHADER
float4 TexturePixelShader(PixelInput input) : SV_TARGET
{
    float4 textureColor;

    // Sample the pixel color from the texture using the sampler at this texture coordinate location.
    textureColor = shaderTexture.Sample(SampleType, input.tex);

    // Color Key / Alpha test
    if(textureColor.a == 0.0f)
    {
        discard;
    }

    // Multiply by vertex color
    float4 finalColor = textureColor * input.color;

    // ============================================================
    // DIRECTDRAW BRIGHTNESS MATCHING (Natural, Non-Artificial)
    // ============================================================
    // DirectDraw appears brighter due to simpler rendering pipeline.
    // We match it naturally with:
    // - Linear brightness multiplier (10% boost)
    // - Tiny ambient lift (raises shadows gently)
    // This is NOT gamma - it's a natural, linear adjustment.
    // ============================================================
    finalColor.rgb *= 1.10;  // 10% brighter (conservative start)
    finalColor.rgb += float3(0.03, 0.03, 0.03);  // Subtle ambient lift

    return finalColor;
}
