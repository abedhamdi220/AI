#!/usr/bin/env python3
"""
AI Image Generation Service with Logo and User Photo Composition
"""
import os
import base64
import io
import logging
from fastapi import FastAPI, HTTPException, Header, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.concurrency import run_in_threadpool
from pydantic import BaseModel
from typing import Optional
from dotenv import load_dotenv
from PIL import Image, ImageChops, ImageFilter
import numpy as np

# Load environment variables
load_dotenv()

logger = logging.getLogger("image_generator")
logging.basicConfig(level=logging.INFO)

app = FastAPI(title="Image Generator Service")

# CORS: يمكن تقييدها عبر ALLOWED_ORIGINS في .env (قائمة مفصولة بفواصل).
# ملاحظة: allow_credentials=True مع allow_origins=["*"] غير صالح وفق سبيسفيكيشن CORS
# (المتصفح سيرفض الاستجابة)، لذا نبقيها False بما إن هذه خدمة داخلية server-to-server
# ولا تحتاج كوكيز.
_allowed_origins = os.environ.get('ALLOWED_ORIGINS', '*')
app.add_middleware(
    CORSMiddleware,
    allow_origins=[o.strip() for o in _allowed_origins.split(',')] if _allowed_origins != '*' else ["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

# مفتاح داخلي اختياري لحماية الخدمة من الاستدعاء المباشر بتخطي Laravel
# (الكوتة، المصادقة، إلخ). فعّلها بضبط INTERNAL_SERVICE_KEY في .env على كل من
# هذه الخدمة و DesignService.php في Laravel (راجع التوصية المرفقة).
INTERNAL_SERVICE_KEY = os.environ.get('INTERNAL_SERVICE_KEY')

async def verify_internal_key(x_internal_key: Optional[str] = Header(None)):
    if INTERNAL_SERVICE_KEY and x_internal_key != INTERNAL_SERVICE_KEY:
        raise HTTPException(status_code=401, detail="Unauthorized")
    return True

# Logo position options — النص والموضع الفعلي يعتمدان على زاوية العرض:
# "الصدر" مفهوم فقط بعرض أمامي. العرض الخلفي له كتفان/أعلى ظهر، والعرض
# الجانبي لا يُظهر إلا شريحة ضيقة من الملابس (لا يوجد "يسار/يمين" حقيقي فيه).
# هذا القاموس يُستخدم في بناء الـ prompt (وصف مكان الشعار للنموذج) وأيضاً
# كمرجع للمواضع الإحداثية الفعلية في LOGO_ANCHORS_BY_ANGLE أدناه، بحيث
# تبقى المنطقة التي نطلب من النموذج تركها فارغة هي نفسها التي سيُلصق فيها
# اللوغو لاحقاً.
LOGO_POSITIONS_BY_ANGLE = {
    "front": {
        "center": "center chest",
        "left": "upper left chest",
        "right": "upper right chest",
        "bottom": "lower front, near the hem",
    },
    "back": {
        "center": "center of the upper back, between the shoulder blades",
        "left": "upper back, left shoulder blade area",
        "right": "upper back, right shoulder blade area",
        "bottom": "lower back, near the hem",
    },
    "side": {
        # عرض جانبي حقيقي يُظهر شريحة ضيقة فقط من الملابس، فكل المواضع
        # تُختزل عملياً لنفس المنطقة القابلة للطباعة.
        "center": "the visible torso area in profile",
        "left": "the visible torso area in profile",
        "right": "the visible torso area in profile",
        "bottom": "the lower hem area visible in profile",
    },
}

# مواضع اللصق الفعلية (نسبة من عرض/ارتفاع الصورة) لكل (زاوية عرض, موضع).
# الإحداثيات هي نقطة المنتصف العلوي للوغو (سيُحوَّل لاحقاً لموضع اللصق الفعلي
# بعد معرفة أبعاد اللوغو بعد تصغيره).
LOGO_ANCHORS_BY_ANGLE = {
    "front": {
        "center": (0.50, 0.25), "left": (0.28, 0.22),
        "right": (0.72, 0.22), "bottom": (0.50, 0.65),
    },
    "back": {
        "center": (0.50, 0.28), "left": (0.32, 0.25),
        "right": (0.68, 0.25), "bottom": (0.50, 0.68),
    },
    "side": {
        "center": (0.50, 0.28), "left": (0.50, 0.28),
        "right": (0.50, 0.28), "bottom": (0.50, 0.65),
    },
}

# نصوص زاوية العرض — كانت request.view_angle تُستقبل وتُمرَّر من الواجهة/Laravel
# بالكامل لكنها لم تُستخدم أبداً في بناء الـ prompt، فكان اختيار المستخدم
# (أمامي/جانبي/خلفي) بلا أي تأثير فعلي على الصورة الناتجة.
VIEW_ANGLE_TEXT = {
    "front": "front view, facing the camera directly",
    "side": "side profile view, shown from the side",
    "back": "back view, showing the rear of the garment"
}

class ImageRequest(BaseModel):
    prompt: str
    clothing_type: str = "t-shirt"
    color: str = ""
    logo_base64: Optional[str] = None
    logo_description: Optional[str] = None
    logo_position: Optional[str] = "center"  # center, left, right, bottom
    user_photo_base64: Optional[str] = None
    view_angle: Optional[str] = "front"

class ImageResponse(BaseModel):
    success: bool
    image_base64: str = ""
    composite_image_base64: str = ""
    revised_prompt: str = ""
    error: str = ""
    # حقول جديدة: توضح للـ caller (Laravel/الواجهة) هل تم فعلاً دمج
    # اللوغو/صورة المستخدم أم لا، بدل ما يفترض النجاح دائماً.
    logo_applied: bool = False
    logo_warning: str = ""
    user_photo_applied: bool = False
    user_photo_warning: str = ""

def to_pil_image(img_data) -> Image.Image:
    """
    تحويل عنصر واحد من نتيجة generate_images() إلى PIL Image بشكل آمن.

    ملاحظة مهمة: نماذج OpenAI من نوع gpt-image-1 ترجع دائماً صور base64
    (b64_json) وليس bytes خام ولا رابط URL. لو المكتبة الوسيطة
    (emergentintegrations) بترجع النتيجة كـ base64 string، فإن استخدام
    io.BytesIO(img_data) مباشرة بيفشل بـ TypeError. هذه الدالة تتعامل مع
    الحالات الثلاث المحتملة (bytes / base64 string / رابط URL) بدل
    افتراض شكل واحد فقط.
    """
    if isinstance(img_data, (bytes, bytearray)):
        return Image.open(io.BytesIO(img_data))

    if isinstance(img_data, str):
        if img_data.startswith("http://") or img_data.startswith("https://"):
            import requests
            resp = requests.get(img_data, timeout=30)
            resp.raise_for_status()
            return Image.open(io.BytesIO(resp.content))

        raw = img_data.split(",", 1)[1] if img_data.startswith("data:") else img_data
        return Image.open(io.BytesIO(base64.b64decode(raw)))

    raise ValueError(f"Unexpected image data type returned by generator: {type(img_data)}")

def decode_base64_image(base64_str: str) -> Image.Image:
    """Decode a base64 string to PIL Image"""
    try:
        # Remove data URL prefix if present
        if ',' in base64_str:
            base64_str = base64_str.split(',')[1]
        image_data = base64.b64decode(base64_str)
        return Image.open(io.BytesIO(image_data))
    except Exception as e:
        raise ValueError(f"Failed to decode image: {e}")

def encode_image_to_base64(image: Image.Image, format: str = "PNG", compress_level: int = 4) -> str:
    """Encode PIL Image to base64 string"""
    buffer = io.BytesIO()
    # compress_level أقل من افتراضي PIL (6) = ترميز أسرع بحجم ملف أكبر شوي —
    # مقبول لصور preview/catalog، مش أرشفة طويلة الأمد.
    save_kwargs = {"compress_level": compress_level} if format.upper() == "PNG" else {}
    image.save(buffer, format=format, **save_kwargs)
    return base64.b64encode(buffer.getvalue()).decode('utf-8')

def _relative_luminance(rgb: np.ndarray) -> float:
    """Return the perceived luminance (0-255) of one RGB value."""
    return float(np.dot(rgb[..., :3], np.array([0.2126, 0.7152, 0.0722])))


def trim_transparent_padding(image: Image.Image, padding: int = 2) -> Image.Image:
    """Crop unused alpha margins so a small visible logo is not scaled down."""
    image = image.convert('RGBA')
    bbox = image.getchannel('A').getbbox()
    if bbox is None:
        return image

    left, top, right, bottom = bbox
    return image.crop((
        max(0, left - padding),
        max(0, top - padding),
        min(image.width, right + padding),
        min(image.height, bottom + padding),
    ))


def remove_logo_background(logo_image: Image.Image, tolerance: float = 30.0, edge_feather: float = 0.35) -> Image.Image:
    """
    Prepare a logo without erasing dark or black ink.

    Transparent PNG alpha is treated as authoritative and its unused padding is
    cropped. An opaque image is colour-keyed only when its corner background is
    confidently flat and there is enough visibly different foreground. The old
    method erased any flat black colour, so a black logo with a black/dark
    background could become transparent. When the ink cannot be reliably
    separated from the background, this function preserves the original image
    rather than silently destroying the logo.
    """
    logo_image = logo_image.convert('RGBA')
    arr = np.array(logo_image, dtype=np.float32)
    height, width = arr.shape[:2]
    original_alpha = arr[..., 3]

    # Preserve intentional transparency exactly; many exported dark logos have
    # transparent margins that must be trimmed before final size calculation.
    if original_alpha.min() < 250:
        return trim_transparent_padding(logo_image)

    corners = np.array([
        arr[0, 0, :3], arr[0, width - 1, :3],
        arr[height - 1, 0, :3], arr[height - 1, width - 1, :3],
    ])
    background = corners.mean(axis=0)
    corners_agree = np.all(np.abs(corners - background) < 15)
    if not corners_agree:
        logger.info("Logo background is not confidently flat; preserving original pixels.")
        return logo_image

    distance = np.sqrt(((arr[..., :3] - background) ** 2).sum(axis=-1))
    foreground_ratio = float(np.mean(distance >= tolerance * 2))
    background_is_dark = _relative_luminance(background) < 55

    # A solid or almost-solid dark bitmap does not contain enough visual data
    # to decide which pixels are ink and which are background. Keep it intact.
    if (background_is_dark and foreground_ratio < 0.01) or foreground_ratio < 0.002 or foreground_ratio > 0.96:
        logger.warning("Logo background removal skipped because foreground separation is unreliable.")
        return logo_image

    output = arr.copy()
    alpha = original_alpha.copy()
    alpha[distance < tolerance] = 0
    transition = (distance >= tolerance) & (distance < tolerance * 2)
    alpha[transition] *= (distance[transition] - tolerance) / tolerance
    output[..., 3] = alpha

    result = trim_transparent_padding(Image.fromarray(output.astype(np.uint8), mode='RGBA'))
    if edge_feather > 0:
        result.putalpha(result.getchannel('A').filter(ImageFilter.GaussianBlur(edge_feather)))
    return result


def warp_logo_for_garment_curve(logo_rgba: Image.Image, curve_strength: float = 0.06) -> Image.Image:
    """
    Apply a very mild dome/barrel warp so the logo reads as wrapped around a
    rounded chest/back instead of sitting perfectly flat like a sticker on
    top of a photo of a garment that clearly isn't flat.

    This is a lightweight approximation, not real 3D projection: it bows the
    logo's top and bottom edges slightly toward the center column, the way a
    flat print visually compresses near the sides of a curved torso. Kept
    subtle on purpose (curve_strength ~6% of the logo's own height) —
    push this higher and it starts reading as a fisheye lens rather than a
    garment surface.
    """
    logo_rgba = logo_rgba.convert('RGBA')
    w, h = logo_rgba.size
    src = np.array(logo_rgba)

    xs = np.arange(w)
    # Parabola: 0 at the left/right edges, 1 at the center column — same
    # shape as a chest/back bulging toward the viewer in the middle.
    norm = (xs - w / 2) / (w / 2)
    bulge = 1 - norm ** 2
    y_shift = (bulge * curve_strength * h)

    # For each output row, sample from (y - shift(x)): shifting the source
    # UP in the middle columns means the center content ends up sitting
    # lower/closer in the output — the same read as a chest curving forward.
    yy, xx = np.mgrid[0:h, 0:w]
    src_y = yy - y_shift[np.newaxis, :]
    src_y_clipped = np.clip(src_y, 0, h - 1)

    y0 = np.floor(src_y_clipped).astype(int)
    y1 = np.clip(y0 + 1, 0, h - 1)
    frac = (src_y_clipped - y0)[..., np.newaxis]
    warped = (src[y0, xx] * (1 - frac) + src[y1, xx] * frac).astype(np.uint8)

    # Rows sampled from outside the original canvas (where the bulge pulled
    # content off the top/bottom) go transparent instead of smearing edge
    # pixels, so this reads as a curve rather than a stretch.
    out_of_bounds = (src_y < 0) | (src_y > h - 1)
    warped[..., 3] = np.where(out_of_bounds, 0, warped[..., 3])

    return Image.fromarray(warped, mode='RGBA')


def blend_logo_on_design(
    design_image: Image.Image,
    logo_image: Image.Image,
    position: str = "center",
    view_angle: str = "front",
) -> Image.Image:
    """
    Blend a logo onto the design image at a position appropriate for the given
    view_angle, and shade the logo using the fabric's own lighting in that spot
    so it reads as printed-on rather than pasted-on-top.
    """
    design = design_image.copy()

    # Ensure design is in RGBA mode
    if design.mode != 'RGBA':
        design = design.convert('RGBA')

    design_width, design_height = design.size

    # تصغير أولي قبل remove_logo_background/warp — كلفة العمليتين numpy
    # تتناسب مع عدد البكسلات، وأقصى حجم نهائي للوغو أصلاً ~25% من عرض
    # التصميم (مثلاً ~256px لتصميم 1024px). فمعالجة رفعة المستخدم الخام
    # (ممكن توصل 3000-4000px من صور الموبايل) قبل أي تصغير كانت بتصرف وقت
    # حقيقي على بكسلات هينرمى أغلبها بالتصغير اللاحق أصلاً. thumbnail()
    # بتصغّر بالمكان وبتحافظ على النسبة وما بتكبّر لو الصورة أصغر من 800.
    logo_image.thumbnail((800, 800), Image.Resampling.LANCZOS)

    # Strip any flat background the uploaded file came with before we do
    # anything else — resizing/pasting an opaque rectangle can't be fixed
    # by the shading pass alone, no matter how good that pass is.
    logo_image = remove_logo_background(logo_image)

    # Resize logo to appropriate size (about 20-25% of design width).
    # Side view gets a smaller cap since it only has a narrow printable strip.
    size_ratio = 0.18 if view_angle == "side" else 0.25
    logo_max_width = int(design_width * size_ratio)
    logo_max_height = int(design_height * size_ratio)

    logo_width, logo_height = logo_image.size
    ratio = min(logo_max_width / logo_width, logo_max_height / logo_height)
    new_logo_size = (max(1, int(logo_width * ratio)), max(1, int(logo_height * ratio)))
    logo_resized = logo_image.resize(new_logo_size, Image.Resampling.LANCZOS)
    logo_resized = warp_logo_for_garment_curve(logo_resized)
    logo_w, logo_h = logo_resized.size

    # Anchor point (fraction of image width/height) for this (view_angle, position)
    # combination — "chest" coordinates only make sense for a front view, so back
    # and side views get their own anchor sets instead of reusing front's.
    angle_anchors = LOGO_ANCHORS_BY_ANGLE.get(view_angle, LOGO_ANCHORS_BY_ANGLE["front"])
    cx, cy = angle_anchors.get(position, angle_anchors["center"])

    x = int(design_width * cx - logo_w / 2)
    y = int(design_height * cy)
    # Keep the logo fully inside the canvas regardless of anchor/size combination
    x = max(0, min(x, design_width - logo_w))
    y = max(0, min(y, design_height - logo_h))

    # --- Contrast-aware realism pass ---------------------------------------
    # Full multiply can make an already-dark logo disappear on a dark garment.
    # Keep the logo's original ink colour dominant and apply only a subtle
    # fabric-lighting contribution. Alpha is never calculated from RGB here.
    region = design.crop((x, y, x + logo_w, y + logo_h)).convert('RGB')
    logo_rgb = logo_resized.convert('RGB')
    logo_alpha = logo_resized.getchannel('A')

    shaded = ImageChops.multiply(logo_rgb, region)
    blended_rgb = Image.blend(logo_rgb, shaded, 0.18)

    alpha_array = np.asarray(logo_alpha, dtype=np.float32) / 255.0
    visible = alpha_array > 0.08
    if np.any(visible):
        logo_array = np.asarray(logo_rgb, dtype=np.float32)
        fabric_array = np.asarray(region, dtype=np.float32)
        logo_luminance = float(np.mean(
            np.dot(logo_array[visible], np.array([0.2126, 0.7152, 0.0722]))
        ))
        fabric_luminance = float(np.mean(
            np.dot(fabric_array[visible], np.array([0.2126, 0.7152, 0.0722]))
        ))

        # If ink and fabric have nearly the same luminance, print a restrained
        # one-pixel underbase. This keeps black marks readable on dark fabric
        # (and light marks readable on light fabric) without changing their
        # actual RGB ink or turning them transparent.
        if abs(logo_luminance - fabric_luminance) < 48:
            underbase_colour = (245, 245, 245, 0) if logo_luminance < 128 else (24, 24, 24, 0)
            underbase_alpha = logo_alpha.filter(ImageFilter.MaxFilter(3)).point(
                lambda value: int(value * 0.72)
            )
            underbase = Image.new('RGBA', (logo_w, logo_h), underbase_colour)
            underbase.putalpha(underbase_alpha)
            design.alpha_composite(underbase, (x, y))

    final_logo = blended_rgb.convert('RGBA')
    final_logo.putalpha(logo_alpha)

    # alpha_composite preserves the source alpha channel accurately, unlike
    # RGB conversion or luminance-derived masks that can make black pixels
    # look transparent.
    design.alpha_composite(final_logo, (x, y))

    return design

def create_composite_with_user_photo(design_image: Image.Image, user_photo: Image.Image) -> Image.Image:
    """
    Create a side-by-side composite image showing user photo next to the design
    """
    # Convert to RGB for final output
    design = design_image.convert('RGB')
    user = user_photo.convert('RGB')

    # Get dimensions
    design_width, design_height = design.size

    # Resize user photo to match design height while maintaining aspect ratio
    user_width, user_height = user.size
    ratio = design_height / user_height
    new_user_width = int(user_width * ratio)
    user_resized = user.resize((new_user_width, design_height), Image.Resampling.LANCZOS)

    # Create composite canvas
    total_width = design_width + new_user_width + 40  # 40px gap
    composite = Image.new('RGB', (total_width, design_height), (255, 255, 255))

    # Paste user photo on the left
    composite.paste(user_resized, (0, 0))

    # Paste design on the right
    composite.paste(design, (new_user_width + 40, 0))

    # Add decorative separator line
    from PIL import ImageDraw
    draw = ImageDraw.Draw(composite)
    separator_x = new_user_width + 20
    draw.line([(separator_x, 20), (separator_x, design_height - 20)], fill=(212, 175, 55), width=3)

    return composite

@app.get("/health")
async def health():
    return {"status": "ok", "service": "image-generator"}

@app.post("/generate", response_model=ImageResponse)
async def generate_image(request: ImageRequest, _auth: bool = Depends(verify_internal_key)):
    try:
        from emergentintegrations.llm.openai.image_generation import OpenAIImageGeneration

        api_key = os.environ.get('EMERGENT_LLM_KEY')
        if not api_key:
            raise HTTPException(status_code=500, detail="API key not configured")

        # Build the prompt with logo description and position if provided.
        # Looked up per view_angle: "chest" text is meaningless for a back/side
        # view, and previously this always used the front-only text regardless
        # of which view was actually being generated.
        angle_position_map = LOGO_POSITIONS_BY_ANGLE.get(request.view_angle, LOGO_POSITIONS_BY_ANGLE["front"])
        logo_position_text = angle_position_map.get(request.logo_position, angle_position_map["center"])

        if request.logo_base64:
            # سيتم لصق الشعار الحقيقي كصورة فعلية فوق التصميم بعد التوليد
            # عبر blend_logo_on_design، لذلك نطلب من النموذج ترك هذه المنطقة
            # نظيفة تماماً بدل رسم شعار وهمي خاص به. النسخة القديمة كانت تطلب
            # من النموذج رسم "شعار مميز" في نفس المكان، فكانت النتيجة شعارين
            # متراكبين/متعارضين بصرياً (الوهمي من الذكاء الاصطناعي + الحقيقي الملصوق).
            logo_part = f" Leave the {logo_position_text} area of the clothing completely plain, clean, and free of any printed graphic, text, or logo — a real logo image will be composited there afterward, so that area must be blank fabric only."
        elif request.logo_description:
            # لا توجد صورة شعار حقيقية، فقط وصف نصي — نطلب من النموذج رسمه فعلياً
            logo_part = f" The clothing has a custom logo/design on the {logo_position_text}: {request.logo_description}."
        else:
            logo_part = ""

        view_angle_text = VIEW_ANGLE_TEXT.get(request.view_angle, VIEW_ANGLE_TEXT["front"])

        # Create enhanced prompt for fashion design
        enhanced_prompt = f"""Professional fashion photography: A {request.clothing_type} clothing item displayed on a mannequin or flat lay, {view_angle_text}.
Design details: {request.prompt}.
{f'Primary color: {request.color}.' if request.color else ''}
{logo_part}
Style: High-end fashion catalog photography, clean white/light gray background, professional studio lighting, sharp details, fabric texture visible, premium quality clothing, fashion e-commerce style photo."""

        # Initialize image generator
        image_gen = OpenAIImageGeneration(api_key=api_key)

        # Generate image
        # quality="low" + مقاس مربّع: أسرع إعداد متاح لـ gpt-image-1 (موصى
        # فيه من توثيق OpenAI للحالات الحساسة للوقت) — مناسب لصور
        # catalog/preview مش طباعة نهائية عالية الدقة. لو مكتبة
        # emergentintegrations ما بتدعم هالباراميترات، لازم تستخدم openai
        # SDK الرسمي مباشرة بدل الـ wrapper.
        images = await image_gen.generate_images(
            prompt=enhanced_prompt,
            model="gpt-image-1",
            number_of_images=1,
            quality="low",

        )

        if not images or len(images) == 0:
            return ImageResponse(
                success=False,
                error="No image was generated"
            )

        # Convert generated image to PIL Image (يدعم bytes/base64/URL بدل افتراض bytes فقط)
        try:
            generated_image = to_pil_image(images[0])
        except Exception as e:
            logger.error(f"Failed to decode generated image: {e}")
            return ImageResponse(
                success=False,
                error=f"تعذر معالجة الصورة الناتجة من مزوّد التوليد: {e}"
            )

        # Process logo if provided - blend it onto the design
        design_with_logo = generated_image
        logo_applied = False
        logo_warning = ""
        if request.logo_base64:
            try:
                logo_image = decode_base64_image(request.logo_base64)
                # run_in_threadpool: هاي معالجة PIL/numpy synchronous، ولو
                # نُفّذت مباشرة جوا الـ async handler بتوقف الـ event loop
                # بالكامل طول مدتها، فطلب تاني وصل بنفس اللحظة ما بيقدر
                # يبلّش أصلاً — وهاد كان سبب محتمل لتراكم الاتصالات تحت حمل
                # متزامن.
                design_with_logo = await run_in_threadpool(
                    blend_logo_on_design,
                    generated_image,
                    logo_image,
                    request.logo_position or "center",
                    request.view_angle or "front"
                )
                logo_applied = True
                logger.info(f"Logo blended successfully at position: {request.logo_position}, view_angle: {request.view_angle}")
                # عرض جانبي حقيقي يُظهر شريحة ضيقة فقط من الملابس، فحتى بعد
                # تصحيح الموضع يبقى الشعار أصغر ووضوحه أقل من الأمامي/الخلفي.
                # نُبلّغ الواجهة بذلك بدل ما يبدو الأمر وكأنه فشل صامت.
                if request.view_angle == "side":
                    logo_warning = "تنويه: في العرض الجانبي تظهر مساحة محدودة من الملابس، لذلك يظهر الشعار أصغر حجماً ووضوحه أقل من العرض الأمامي أو الخلفي."
            except Exception as e:
                # لا نُسقط الطلب بالكامل بسبب فشل دمج اللوغو، لكن لازم نُبلّغ
                # الـ caller بوضوح إن اللوغو لم يُدمج فعلياً بدل الافتراض الصامت
                # إن كل شيء تم بنجاح.
                logger.warning(f"Could not blend logo: {e}")
                logo_warning = f"تعذر دمج الشعار: {e}"
                design_with_logo = generated_image

        # Encode the design (with logo if applied)
        design_base64 = await run_in_threadpool(
            encode_image_to_base64, design_with_logo.convert('RGB'), "PNG"
        )

        # Create composite with user photo if provided
        composite_base64 = ""
        user_photo_applied = False
        user_photo_warning = ""
        if request.user_photo_base64:
            try:
                user_photo = decode_base64_image(request.user_photo_base64)
                composite_image = await run_in_threadpool(
                    create_composite_with_user_photo, design_with_logo, user_photo
                )
                composite_base64 = await run_in_threadpool(
                    encode_image_to_base64, composite_image, "PNG"
                )
                user_photo_applied = True
                logger.info("Composite image with user photo created successfully")
            except Exception as e:
                logger.warning(f"Could not create composite with user photo: {e}")
                user_photo_warning = f"تعذر دمج صورتك مع التصميم: {e}"

        return ImageResponse(
            success=True,
            image_base64=design_base64,
            composite_image_base64=composite_base64,
            revised_prompt=enhanced_prompt,
            logo_applied=logo_applied,
            logo_warning=logo_warning,
            user_photo_applied=user_photo_applied,
            user_photo_warning=user_photo_warning
        )

    except Exception as e:
        logger.error(f"Error generating image: {e}")
        return ImageResponse(
            success=False,
            error=str(e)
        )

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8002)
