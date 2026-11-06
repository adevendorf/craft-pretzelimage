# Pretzel Image Changelog

 Craft 4 

• Requires Craft 4 and Intervention Image 4 (PHP 8.3+); explicit GD/Imagick driver (`PRETZEL_DRIVER`)
• AVIF/GIF output, EXIF auto-orientation, fixed portrait dimension math
• SVGs are copied unmodified and only from SVG image assets; no path traversal
• Responses use Craft's Response with cache headers; width/height capped at 5000

 Craft 3 

• Adding Webp Support
• Added headers to prevent caching on image generation
• Fixed a bug with allowAnonymous