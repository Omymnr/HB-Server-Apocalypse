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

    // VISUAL FIX: TEMPORARILY DISABLED TO DEBUG SPRITE VISIBILITY
    // finalColor.rgb = lerp(float3(0.030f, 0.030f, 0.030f), float3(1.0f, 1.0f, 1.0f), finalColor.rgb);

    return finalColor;
}
