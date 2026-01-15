# Search and Filter API Documentation

## Overview
All course and category APIs support comprehensive search and filtering capabilities. This document outlines all available search and filter options.

---

## Course Search API

### Endpoint
`GET /api/courses`

### Search Parameter
- **Parameter:** `search`
- **Type:** String
- **Description:** Searches across course title, short description, full description, and tags
- **Example:** `GET /api/courses?search=agriculture`

### Filter Parameters

#### Category Filter
- **Parameter:** `category_id`
- **Type:** Integer
- **Description:** Filter courses by category
- **Example:** `GET /api/courses?category_id=1`

#### Level Filter
- **Parameter:** `level`
- **Type:** String
- **Options:** `beginner`, `intermediate`, `advanced`
- **Description:** Filter courses by difficulty level
- **Example:** `GET /api/courses?level=beginner`

#### Rating Filter
- **Parameter:** `min_rating`
- **Type:** Decimal (0.0 - 5.0)
- **Description:** Filter courses with minimum rating
- **Example:** `GET /api/courses?min_rating=4.0`

#### Duration Filters
- **Parameter:** `min_duration`
- **Type:** Integer (minutes)
- **Description:** Filter courses with minimum duration
- **Example:** `GET /api/courses?min_duration=60`

- **Parameter:** `max_duration`
- **Type:** Integer (minutes)
- **Description:** Filter courses with maximum duration
- **Example:** `GET /api/courses?max_duration=120`

#### Pagination
- **Parameter:** `per_page`
- **Type:** Integer
- **Default:** 20
- **Description:** Number of results per page
- **Example:** `GET /api/courses?per_page=10`

### Combined Example
```
GET /api/courses?search=farming&category_id=1&level=beginner&min_rating=4.0&min_duration=30&max_duration=120&per_page=15
```

### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Course Title",
      "slug": "course-slug",
      "short_description": "Brief description",
      "image_url": "https://...",
      "preview_video_url": "https://...",
      "rating": 4.5,
      "rating_count": 100,
      "enrollment_count": 500,
      "duration_minutes": 90,
      "level": "beginner",
      "lessons_count": 15,
      "certificate_included": true,
      "category": {
        "id": 1,
        "name": "Agriculture",
        "slug": "agriculture"
      },
      "main_instructor": {...},
      "instructors": [...],
      "is_enrolled": false
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "from": 1,
    "to": 20
  },
  "message": "Courses retrieved successfully"
}
```

---

## Category Search API

### Endpoint
`GET /api/categories`

### Search Parameter
- **Parameter:** `search`
- **Type:** String
- **Description:** Searches across category name and description
- **Example:** `GET /api/categories?search=agriculture`

### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Agriculture",
      "slug": "agriculture",
      "description": "Category description",
      "image": "https://...",
      "is_active": true
    }
  ],
  "message": "Categories retrieved successfully"
}
```

---

## Category Courses Search API

### Endpoint
`GET /api/categories/{category_id}/courses`

### Search Parameter
- **Parameter:** `search`
- **Type:** String
- **Description:** Searches across course title, description, and tags within the category
- **Example:** `GET /api/categories/1/courses?search=farming`

### Filter Parameters

#### Level Filter
- **Parameter:** `level`
- **Type:** String
- **Options:** `beginner`, `intermediate`, `advanced`
- **Example:** `GET /api/categories/1/courses?level=beginner`

#### Rating Filter
- **Parameter:** `min_rating`
- **Type:** Decimal
- **Example:** `GET /api/categories/1/courses?min_rating=4.0`

#### Duration Filters
- **Parameter:** `min_duration`
- **Type:** Integer (minutes)
- **Example:** `GET /api/categories/1/courses?min_duration=60`

- **Parameter:** `max_duration`
- **Type:** Integer (minutes)
- **Example:** `GET /api/categories/1/courses?max_duration=120`

#### Pagination
- **Parameter:** `per_page`
- **Type:** Integer
- **Default:** 20
- **Example:** `GET /api/categories/1/courses?per_page=10`

### Combined Example
```
GET /api/categories/1/courses?search=farming&level=beginner&min_rating=4.0&per_page=15
```

### Response Format
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 50
  },
  "message": "Category courses retrieved successfully"
}
```

---

## Search Behavior

### Course Search
The search parameter searches across:
1. **Title** - Course title (partial match)
2. **Short Description** - Brief course description (partial match)
3. **Description** - Full course description (partial match)
4. **Tags** - Course tags (exact match in JSON array)

### Category Search
The search parameter searches across:
1. **Name** - Category name (partial match)
2. **Description** - Category description (partial match)

### Search Logic
- All search terms are case-insensitive
- Uses `LIKE` queries for partial matching
- Multiple words in search term are matched individually
- Tags use JSON contains for exact matching

---

## Filter Combinations

All filters can be combined for precise results:

```
GET /api/courses?search=organic&category_id=1&level=intermediate&min_rating=4.5&min_duration=60&max_duration=180&per_page=20
```

This will return:
- Courses matching "organic" in title/description/tags
- In category 1
- At intermediate level
- With rating >= 4.5
- Duration between 60-180 minutes
- 20 results per page

---

## Performance Notes

- All search queries are optimized with database indexes
- Results are cached for 5 minutes (user-specific) or 10 minutes (public)
- Search is case-insensitive for better user experience
- Pagination is recommended for large result sets

---

## Error Responses

### Invalid Category
```json
{
  "success": false,
  "message": "Category not found"
}
```
**Status Code:** 404

### Invalid Filter Value
Filters with invalid values are ignored (e.g., invalid level value)

---

**Last Updated:** January 2025

