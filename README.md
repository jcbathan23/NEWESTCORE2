# SLATE Freight Management System - CORE II

A comprehensive freight management system with role-based access control and separate dashboards for different user types.

## Features

### 🔐 Authentication System
- **User Registration**: Support for both regular users and service providers
- **Role-Based Access Control**: Three distinct user roles (Admin, Service Provider, Regular User)
- **Secure Login**: Password hashing, rate limiting, and session management
- **Session Security**: 8-hour timeout with automatic logout

### 👥 User Roles

#### 1. **Admin Users**
- Access to admin dashboard (`admin.php`)
- User management capabilities
- System-wide statistics and monitoring
- Access to all system features

#### 2. **Service Providers**
- Access to provider dashboard (`provider-dashboard.php`)
- Service management and monitoring
- Revenue tracking and performance metrics
- Service area and type management

#### 3. **Regular Users**
- Access to user dashboard (`user-dashboard.php`)
- Service request management
- Service provider browsing
- Personal service history

### 📊 Dashboard Features

#### Admin Dashboard
- System overview statistics
- User management
- Password change functionality
- Security information display

#### Provider Dashboard
- Active services count
- Monthly revenue tracking
- Service area coverage
- Performance rating display
- Service performance charts

#### User Dashboard
- Active requests tracking
- Completed services history
- Total spending overview
- Satisfaction rate metrics
- Service usage charts

## File Structure

```
├── index.php                 # Entry point with role-based redirects
├── login.php                 # User login form
├── register.php              # User registration form
├── auth.php                  # Authentication logic and user management
├── admin.php                 # Admin dashboard
├── provider-dashboard.php    # Service provider dashboard
├── user-dashboard.php        # Regular user dashboard
├── landpage.php              # Legacy admin dashboard (redirects to appropriate dashboard)
├── db.php                    # Database connection and table creation
├── security.php              # Security headers and CSRF protection
├── api/                      # API endpoints
│   ├── users.php            # User management API
│   ├── change-password.php  # Password change API
│   └── ...                  # Other API endpoints
└── slatelogo.png            # System logo
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'provider') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### User Profiles Table
```sql
CREATE TABLE user_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    company VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Providers Table
```sql
CREATE TABLE providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50) NOT NULL,
    service_area VARCHAR(255) NOT NULL,
    monthly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) NOT NULL,
    contract_start DATE NOT NULL,
    contract_end DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Installation & Setup

### Prerequisites
- WAMP/XAMPP server with PHP 7.4+
- MySQL 5.7+
- Modern web browser

### Setup Steps

1. **Clone/Download** the project to your web server directory
2. **Database Setup**:
   - Create a MySQL database named `core2`
   - Update database credentials in `db.php` if needed
   - The system will auto-create required tables on first run

3. **Default Admin Account**:
   - Username: `admin`
   - Password: `admin123`
   - Email: `admin@slate.com`

4. **Access the System**:
   - Navigate to your project URL
   - The system will automatically redirect you to the appropriate page

## Usage

### Registration Process

1. **Navigate to Registration Page**:
   - Click "Register here" on the login page
   - Or directly access `register.php`

2. **Choose User Type**:
   - **Regular User**: Basic account for service requests
   - **Service Provider**: Business account for offering services

3. **Fill Required Information**:
   - Username and email (must be unique)
   - Password (minimum 6 characters)
   - Role-specific information

4. **Account Activation**:
   - Accounts are automatically activated
   - Redirected to login page after successful registration

### Login Process

1. **Enter Credentials**:
   - Username/email and password
   - System validates credentials and role

2. **Role-Based Redirect**:
   - **Admin**: Redirected to admin dashboard
   - **Provider**: Redirected to provider dashboard
   - **User**: Redirected to user dashboard

### Dashboard Navigation

Each dashboard includes:
- **Sidebar Navigation**: Role-specific menu items
- **Statistics Cards**: Key metrics and performance indicators
- **Charts**: Visual data representation
- **Theme Toggle**: Dark/light mode switching
- **Responsive Design**: Mobile-friendly interface

## Security Features

- **Password Hashing**: Bcrypt encryption for all passwords
- **CSRF Protection**: Token-based request validation
- **Rate Limiting**: Login attempt restrictions
- **Session Management**: Secure session handling
- **Input Sanitization**: XSS and injection prevention
- **Security Headers**: Modern browser security features

## Customization

### Adding New User Roles
1. Update the `role` ENUM in the users table
2. Add role-specific functions in `auth.php`
3. Create corresponding dashboard files
4. Update redirect logic in authentication files

### Modifying Dashboard Content
- Edit the respective dashboard PHP files
- Update CSS variables in the `:root` selector
- Modify JavaScript functions for dynamic content
- Add new API endpoints as needed

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Verify database credentials in `db.php`
   - Ensure MySQL service is running
   - Check database name exists

2. **Registration Fails**:
   - Verify all required fields are filled
   - Check username/email uniqueness
   - Ensure password meets minimum requirements

3. **Login Redirect Issues**:
   - Clear browser cache and cookies
   - Verify session configuration
   - Check file permissions

### Debug Mode

Enable error reporting by adding to PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Support

For technical support or feature requests, please contact the development team.

## License

This project is proprietary software. All rights reserved.

---

**Version**: 2.0  
**Last Updated**: 2024  
**System**: SLATE Freight Management System - CORE II
