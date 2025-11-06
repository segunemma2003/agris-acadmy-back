# AgriSiti Academy - Learning Management System

A comprehensive Learning Management System (LMS) built with Laravel and Filament, designed to handle 100,000+ users with multiple concurrent requests.

## Features

### Core Features
- ✅ User Management (Admin, Tutor, Student roles)
- ✅ Course Management with Categories
- ✅ Module & Topic Structure
- ✅ Enrollment System with Codes
- ✅ Progress Tracking
- ✅ Student Notes
- ✅ Assignments & Submissions
- ✅ CBT Tests (Computer-Based Tests)
- ✅ Messaging System
- ✅ Certificates
- ✅ Course Reviews & Ratings
- ✅ VR Learning Content
- ✅ DIY Projects
- ✅ Course Recommendations
- ✅ Downloadable Resources

### Admin Panel
- Full system management via Laravel Filament
- User management (create, edit, delete users)
- Category management
- Course approval and management
- Enrollment code generation
- Analytics and reporting

### Tutor Panel
- Course creation and management
- Module and topic management
- Assignment creation and grading
- Test creation and management
- Student progress monitoring
- Message management
- Enrollment code generation

### API for Frontend
- RESTful API with Laravel Sanctum authentication
- Complete CRUD operations
- Real-time progress tracking
- File upload support
- Comprehensive error handling

## Technology Stack

- **Backend**: Laravel 12
- **Admin Panel**: Filament 3
- **Authentication**: Laravel Sanctum
- **Database**: MySQL/PostgreSQL/SQLite
- **File Storage**: Local/S3 compatible

## Installation

See [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) for detailed installation instructions.

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link

# Build assets
npm run build
```

## Documentation

- [API Documentation](API_DOCUMENTATION.md) - Complete API reference
- [User Stories](USER_STORIES.md) - Detailed user stories for all features
- [Implementation Guide](IMPLEMENTATION_GUIDE.md) - Setup and deployment guide

## API Endpoints

### Public Endpoints
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `GET /api/categories` - Get all categories
- `GET /api/courses` - Get all courses
- `GET /api/categories-with-courses` - Get categories with courses

### Protected Endpoints
- `GET /api/user` - Get current user
- `POST /api/enroll` - Enroll in course
- `GET /api/my-courses` - Get user's courses
- `GET /api/courses/{id}/progress` - Get course progress
- `POST /api/notes` - Create note
- `GET /api/assignments` - Get assignments
- `POST /api/messages` - Send message

See [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for complete API documentation.

## Access Points

- **Admin Panel**: `/admin`
- **Tutor Panel**: `/tutor`
- **API Base URL**: `/api`

## System Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.8+

## Performance

- Optimized for 100,000+ concurrent users
- Database indexing for fast queries
- Efficient relationship loading
- Caching support

## Security

- Laravel Sanctum for API authentication
- Role-based access control
- Password hashing
- Input validation
- SQL injection prevention
- XSS protection

## License

This project is proprietary software.

## Support

For issues and questions, please contact the development team.

---

**Built with ❤️ using Laravel and Filament**
