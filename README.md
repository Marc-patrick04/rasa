# RASA Admin Panel

A responsive web application for managing the Rwanda Anglican Student Association (RASA) nominations and previous leaders at RP MUSANZE COLLEGE.

## Features

- 📱 **Mobile-Responsive Design**: Complete mobile toggle functionality for all admin pages
- 👥 **Candidate Management**: Add, edit, delete, and manage candidates
- 🏆 **Previous Leaders**: Track and manage past RASA leaders
- 📊 **Statistics Dashboard**: View nomination statistics and trends
- 🔐 **Admin Authentication**: Secure admin login system
- 📤 **Data Export**: Export data in multiple formats (CSV, Excel, PDF, JSON, XML)
- 🌐 **Multi-Environment Support**: Works with both local and Neon PostgreSQL

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: PostgreSQL
- **Authentication**: PHP Sessions
- **Styling**: Custom CSS with responsive design

## Installation & Setup

### 1. Local Development Setup

#### Prerequisites
- PHP 7.4 or higher
- PostgreSQL database server
- Web server (Apache/Nginx)

#### Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd rasa
   ```

2. **Set up PostgreSQL database**
   ```sql
   CREATE DATABASE rasa_db;
   CREATE USER rasa_user WITH PASSWORD 'your_password';
   GRANT ALL PRIVILEGES ON DATABASE rasa_db TO rasa_user;
   ```

3. **Import database schema**
   ```bash
   psql -U rasa_user -d rasa_db -f dd.sql
   ```

4. **Configure database connection**
   Edit `includes/config.php` and update the local database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'rasa_db');
   define('DB_USER', 'rasa_user');
   define('DB_PASS', 'your_password');
   ```

5. **Set up web server**
   - Point your web server to the project directory
   - Ensure PHP is enabled and configured

6. **Access the application**
   - Visit `http://localhost/rasa` in your browser
   - Default admin login: username: `admin`, password: `admin123`

### 2. Production Setup with Neon PostgreSQL

#### Prerequisites
- Neon PostgreSQL account (https://neon.tech)
- Web hosting with PHP support

#### Steps

1. **Create Neon PostgreSQL database**
   - Sign up at https://neon.tech
   - Create a new project and database
   - Note your connection string

2. **Set environment variable**
   Add the following environment variable to your hosting environment:
   ```bash
   DATABASE_URL=postgresql://neondb_owner:npg_jfl4Z2BHwxWk@ep-rough-poetry-ad9eiji7-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require
   ```

3. **Upload files to server**
   - Upload all project files to your web hosting
   - Ensure proper file permissions

4. **Import database schema**
   Use Neon's SQL editor or psql to run the `dd.sql` file

5. **Access your application**
   - Visit your production domain
   - The application will automatically detect and use Neon PostgreSQL

## Environment Configuration

The application automatically detects the environment:

### Local Development
- Automatically uses local PostgreSQL configuration
- `DB_SSLMODE` set to `prefer`
- Site URL: `http://localhost/rasa`

### Production (Neon PostgreSQL)
- Detected when `DATABASE_URL` environment variable is set
- `DB_SSLMODE` set to `require`
- Site URL: `https://your-production-domain.com`

## Database Schema

The application uses the following tables:

- **users**: Admin user accounts
- **positions**: RASA positions available for nomination
- **candidates**: Student nominations
- **previous_leaders**: Historical RASA leaders

## Admin Features

### Dashboard
- View statistics and recent activity
- Quick access to all management sections

### Positions Management
- Add, edit, delete positions
- Mobile-responsive interface

### Candidates Management
- View all nominations with filters
- Bulk actions and export options
- Toggle candidate status
- Mobile-responsive interface

### Previous Leaders Management
- Add and manage past leaders
- Photo upload support
- Export functionality
- Mobile-responsive interface

### Settings
- Change admin password
- Configure site settings
- Manage admin users
- Mobile-responsive interface

## Mobile Features

All admin pages include:
- 📱 Hamburger menu toggle button
- 🎯 Smooth slide animations
- 👆 Touch-friendly interface
- 🎨 Consistent visual design
- 🔍 Responsive layouts

## Security Features

- Password hashing with bcrypt
- Session-based authentication
- Input validation and sanitization
- SQL injection prevention with prepared statements
- CSRF protection for forms

## Troubleshooting

### Database Connection Issues

**Local Development:**
- Ensure PostgreSQL server is running
- Check database credentials in `includes/config.php`
- Verify database schema is imported

**Neon PostgreSQL:**
- Ensure `DATABASE_URL` environment variable is set
- Check Neon dashboard for connection status
- Verify SSL mode is set to `require`

### Common Issues

1. **"Connection failed" error**
   - Check database server status
   - Verify credentials and permissions
   - Ensure proper environment configuration

2. **"Class not found" errors**
   - Ensure all PHP files are uploaded
   - Check file permissions
   - Verify PHP version compatibility

3. **Mobile toggle not working**
   - Check JavaScript console for errors
   - Ensure all CSS and JS files are loaded
   - Verify mobile toggle button HTML is present

## Support

For issues and support:
- Check the troubleshooting section above
- Review server error logs
- Ensure all prerequisites are met
- Verify file permissions and configurations

## License

This project is open source and available under the [MIT License](LICENSE).

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Contact

For questions or support, please contact the development team.