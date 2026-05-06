#!/usr/bin/env python3
"""
MOCK AI Image Generation Service 
(للاختبار والتأكد من دورة المشروع دون استخدام API حقيقي)
"""
import os
import base64
import io
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
from PIL import Image, ImageDraw

app = FastAPI(title="Mock Image Generator Service")

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Logo position options
LOGO_POSITIONS = {
    "center": "center chest",
    "left": "upper left chest",
    "right": "upper right chest",
    "bottom": "bottom center"
}

class ImageRequest(BaseModel):
    prompt: str
    clothing_type: str = "t-shirt"
    color: str = ""
    logo_base64: Optional[str] = None
    logo_description: Optional[str] = None
    logo_position: Optional[str] = "center"
    user_photo_base64: Optional[str] = None
    view_angle: Optional[str] = "front"

class ImageResponse(BaseModel):
    success: bool
    image_base64: str = ""
    composite_image_base64: str = ""
    revised_prompt: str = ""
    error: str = ""

def decode_base64_image(base64_str: str) -> Image.Image:
    try:
        if ',' in base64_str:
            base64_str = base64_str.split(',')[1]
        image_data = base64.b64decode(base64_str)
        return Image.open(io.BytesIO(image_data))
    except Exception as e:
        raise ValueError(f"Failed to decode image: {e}")

def encode_image_to_base64(image: Image.Image, format: str = "PNG") -> str:
    buffer = io.BytesIO()
    image.save(buffer, format=format)
    return base64.b64encode(buffer.getvalue()).decode('utf-8')

def blend_logo_on_design(design_image: Image.Image, logo_image: Image.Image, position: str = "center") -> Image.Image:
    design = design_image.copy()
    if design.mode != 'RGBA':
        design = design.convert('RGBA')
    
    design_width, design_height = design.size
    logo_max_width = int(design_width * 0.25)
    logo_max_height = int(design_height * 0.25)
    
    logo_image = logo_image.convert('RGBA')
    logo_width, logo_height = logo_image.size
    ratio = min(logo_max_width / logo_width, logo_max_height / logo_height)
    new_logo_size = (int(logo_width * ratio), int(logo_height * ratio))
    logo_resized = logo_image.resize(new_logo_size, Image.Resampling.LANCZOS)
    
    logo_w, logo_h = logo_resized.size
    
    if position == "center":
        x = (design_width - logo_w) // 2
        y = int(design_height * 0.25)
    elif position == "left":
        x = int(design_width * 0.15)
        y = int(design_height * 0.2)
    elif position == "right":
        x = int(design_width * 0.85) - logo_w
        y = int(design_height * 0.2)
    elif position == "bottom":
        x = (design_width - logo_w) // 2
        y = int(design_height * 0.65)
    else:
        x = (design_width - logo_w) // 2
        y = int(design_height * 0.25)
    
    design.paste(logo_resized, (x, y), logo_resized)
    return design

def create_composite_with_user_photo(design_image: Image.Image, user_photo: Image.Image) -> Image.Image:
    design = design_image.convert('RGB')
    user = user_photo.convert('RGB')
    
    design_width, design_height = design.size
    user_width, user_height = user.size
    ratio = design_height / user_height
    new_user_width = int(user_width * ratio)
    user_resized = user.resize((new_user_width, design_height), Image.Resampling.LANCZOS)
    
    total_width = design_width + new_user_width + 40
    composite = Image.new('RGB', (total_width, design_height), (255, 255, 255))
    
    composite.paste(user_resized, (0, 0))
    composite.paste(design, (new_user_width + 40, 0))
    
    draw = ImageDraw.Draw(composite)
    separator_x = new_user_width + 20
    draw.line([(separator_x, 20), (separator_x, design_height - 20)], fill=(212, 175, 55), width=3)
    
    return composite

@app.get("/health")
async def health():
    return {"status": "ok", "service": "mock-image-generator"}

@app.post("/generate", response_model=ImageResponse)
async def generate_image(request: ImageRequest):
    try:
        # هنا لن نقوم بالاتصال بأي API خارجي
        # سنقوم برسم صورة وهمية (Mock Image) بحجم 512x512
        
        # 1. إنشاء صورة بخلفية رمادية فاتحة
        bg_color = (230, 230, 230)
        generated_image = Image.new('RGB', (512, 512), color=bg_color)
        
        # 2. كتابة تفاصيل الطلب على الصورة لنتأكد من أن البيانات تصل بشكل صحيح
        draw = ImageDraw.Draw(generated_image)
        
        mock_text = f"""
        [ MOCK DESIGN ]
        
        Type: {request.clothing_type}
        Color: {request.color or 'N/A'}
        Angle: {request.view_angle}
        
        Prompt:
        {request.prompt[:30]}...
        """
        
        # رسم النص في منتصف الصورة تقريباً
        draw.text((50, 150), mock_text, fill=(50, 50, 50))
        
        # 3. محاكاة دمج اللوجو (اللوجو الحقيقي الذي يرفعه المستخدم سيتم دمجه)
        design_with_logo = generated_image
        if request.logo_base64:
            try:
                logo_image = decode_base64_image(request.logo_base64)
                design_with_logo = blend_logo_on_design(
                    generated_image, 
                    logo_image, 
                    request.logo_position or "center"
                )
            except Exception as e:
                print(f"Warning: Could not blend logo: {e}")
        
        # 4. تحويل الصورة النهائية إلى Base64
        design_base64 = encode_image_to_base64(design_with_logo.convert('RGB'), "PNG")
        
        # 5. محاكاة الصورة المدمجة مع صورة المستخدم
        composite_base64 = ""
        if request.user_photo_base64:
            try:
                user_photo = decode_base64_image(request.user_photo_base64)
                composite_image = create_composite_with_user_photo(design_with_logo, user_photo)
                composite_base64 = encode_image_to_base64(composite_image, "PNG")
            except Exception as e:
                print(f"Warning: Could not create composite with user photo: {e}")
        
        return ImageResponse(
            success=True,
            image_base64=design_base64,
            composite_image_base64=composite_base64,
            revised_prompt=f"[MOCK] {request.prompt}"
        )
            
    except Exception as e:
        print(f"Error generating mock image: {e}")
        return ImageResponse(
            success=False,
            error=str(e)
        )

if __name__ == "__main__":
    import uvicorn
    # تأكد من تشغيل السيرفر الوهمي على نفس البورت 8002
    uvicorn.run(app, host="0.0.0.0", port=8002)