# Image Upload Dimensions Guide

This document specifies the recommended dimensions for all image uploads in the Agrisiti Academy application.

## Overview

All images should be optimized for web use. Recommended formats:
- **Format:** JPEG or PNG
- **JPEG:** Best for photos and complex images
- **PNG:** Best for images with transparency or simple graphics
- **Max File Size:** 5MB per image (enforced by server)

---

## 1. Course Images

**Location:** Course cover images  
**Field:** `image` in `courses` table  
**Directory:** `storage/app/public/courses`

### Recommended Dimensions:
- **Width:** 1200px
- **Height:** 675px
- **Aspect Ratio:** 16:9
- **Minimum:** 800px × 450px
- **Maximum:** 1920px × 1080px

### Usage:
- Displayed on course cards
- Course detail pages
- Featured course sections

### Notes:
- Images are automatically resized by Filament
- Maintain 16:9 aspect ratio for best display
- Use high-quality images for better visual appeal

---

## 2. Category Images

**Location:** Category images  
**Field:** `image` in `categories` table  
**Directory:** `storage/app/public/categories`

### Recommended Dimensions:
- **Width:** 800px
- **Height:** 800px
- **Aspect Ratio:** 1:1 (Square)
- **Minimum:** 400px × 400px
- **Maximum:** 1200px × 1200px

### Usage:
- Category cards
- Category listing pages
- Navigation icons

### Notes:
- Square format works best for category icons
- Can be circular or rounded square in display
- Use simple, recognizable images

---

## 3. User Avatars

**Location:** User profile pictures  
**Field:** `avatar` in `users` table  
**Directory:** `storage/app/public/avatars`

### Recommended Dimensions:
- **Width:** 400px
- **Height:** 400px
- **Aspect Ratio:** 1:1 (Square)
- **Minimum:** 200px × 200px
- **Maximum:** 800px × 800px

### Usage:
- User profile pages
- Course tutor display
- Comments and messages
- Student listings

### Notes:
- Displayed as circular images in the UI
- Square images will be cropped to circle
- Use clear, centered portraits for best results

---

## 4. Course Resources

**Location:** Resource thumbnails (if applicable)  
**Field:** `image` in `course_resources` table (if exists)  
**Directory:** `storage/app/public/resources`

### Recommended Dimensions:
- **Width:** 600px
- **Height:** 800px
- **Aspect Ratio:** 3:4 (Portrait)
- **Minimum:** 300px × 400px
- **Maximum:** 900px × 1200px

### Notes:
- Only if resource thumbnails are implemented
- Portrait orientation for document previews

---

## 5. DIY Content Images

**Location:** DIY project images  
**Field:** `image` in `course_diy_content` table  
**Directory:** `storage/app/public/diy`

### Recommended Dimensions:
- **Width:** 1200px
- **Height:** 800px
- **Aspect Ratio:** 3:2
- **Minimum:** 800px × 533px
- **Maximum:** 1920px × 1280px

### Usage:
- DIY project instructions
- Step-by-step guides
- Project galleries

---

## 6. VR Content Images

**Location:** VR content thumbnails  
**Field:** `image` in `course_vr_content` table  
**Directory:** `storage/app/public/vr`

### Recommended Dimensions:
- **Width:** 1200px
- **Height:** 675px
- **Aspect Ratio:** 16:9
- **Minimum:** 800px × 450px
- **Maximum:** 1920px × 1080px

### Notes:
- Similar to course images
- Used for VR content previews

---

## Image Optimization Tips

1. **Compression:**
   - Use tools like TinyPNG, ImageOptim, or Squoosh
   - Aim for 80-90% quality for JPEG
   - Use lossless compression for PNG

2. **Responsive Images:**
   - Consider providing multiple sizes for different devices
   - Use `srcset` in frontend for responsive images

3. **File Naming:**
   - Use descriptive, lowercase filenames
   - Avoid spaces (use hyphens or underscores)
   - Example: `introduction-to-farming-cover.jpg`

4. **Alt Text:**
   - Always provide meaningful alt text for accessibility
   - Describe the image content clearly

---

## Current Implementation

All image uploads in Filament use:
- **Storage Disk:** `public`
- **Validation:** Automatic image validation
- **Resizing:** Handled by Filament/Intervention Image (if configured)
- **Access:** Images accessible via `/storage/{directory}/{filename}`

---

## Testing Image Uploads

When testing image uploads:
1. Upload images with different dimensions
2. Verify images are stored correctly
3. Check image display in the UI
4. Test with images larger than recommended (should be resized)
5. Test with images smaller than minimum (may appear pixelated)

---

## Future Enhancements

Consider implementing:
- Automatic image resizing on upload
- Multiple image sizes (thumbnails, medium, large)
- Image CDN integration
- Lazy loading for better performance
- WebP format support for better compression

---

**Last Updated:** January 2025


