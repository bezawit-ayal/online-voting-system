# Online Voting System

A modern, secure online voting system built with HTML, CSS, JavaScript, PHP, and MySQL.

## Features

- **User Authentication**: Secure login and registration system
- **Voting Interface**: Modern, intuitive voting interface
- **Real-time Results**: View election results with progress bars
- **Admin Panel**: Complete administration dashboard
  - Manage candidates
  - Manage voters
  - Configure election settings
  - View detailed results
- **Security**: Password hashing with bcrypt, session management
- **Responsive Design**: Mobile-friendly interface
- **Vote Tracking**: Prevent duplicate voting
- **Election Settings**: Configure voting schedule and status

## Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx server
- XAMPP (recommended for development)

### Steps

1. **Create Database**
   - Import `database/schema.sql` into your MySQL database
   ```bash
   mysql -u root -p voting_system < database/schema.sql
   ```

2. **Configure Database**
   - Edit `config/db.php` with your database credentials
   - Default: localhost, root, no password

3. **Place Files**
   - Copy all files to your web root (e.g., `htdocs/online-voting-system`)

4. **Set Permissions**
   - Ensure the web server can write to necessary directories

## Usage

### For Voters
1. Register or login at `/login.php`
2. Go to `/voting.php` to cast votes
3. Review selections and confirm
4. View results at `/results.php`

### For Administrators
1. Login with admin credentials (username: `admin`, password: `admin123`)
2. Access admin panel at `/admin/index.php`
3. Manage candidates: `/admin/candidates.php`
4. Manage voters: `/admin/users.php`
5. Configure settings: `/admin/settings.php`
6. View detailed results: `/admin/results.php`

## Default Admin Credentials
- **Username**: admin
- **Password**: admin123
- **Email**: admin@voting.com

⚠️ **Important**: Change these credentials immediately after installation!

## Project Structure

```
online-voting-system/
├── admin/                    # Admin panel pages
│   ├── index.php            # Admin dashboard
│   ├── candidates.php       # Manage candidates
│   ├── users.php            # Manage voters
│   ├── settings.php         # Election settings
│   └── results.php          # Detailed results
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   └── js/
│       └── script.js        # JavaScript functionality
├── config/
│   └── db.php               # Database configuration
├── database/
│   └── schema.sql           # Database schema
├── includes/
│   ├── auth.php             # Authentication functions
│   ├── functions.php        # General functions
│   └── api.php              # API endpoints
├── index.php                # Dashboard
├── login.php                # Login page
├── register.php             # Registration page
├── voting.php               # Voting interface
├── results.php              # Public results page
├── voting_complete.php      # Success page
└── logout.php               # Logout handler
```

## Database Schema

### Users Table
- Stores user accounts and voting status
- Tracks which users have voted

### Candidates Table
- Stores candidate information
- Associated with specific positions

### Votes Table
- Records individual votes
- Links users to candidates

### Election Settings Table
- Stores election configuration
- Controls voting status and schedule

## Security Features

- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (HTML escaping)
- ✅ Session management
- ✅ Admin role verification
- ✅ Duplicate vote prevention

## API Endpoints

### `/includes/api.php`
- `POST /includes/api.php` - Cast votes
- `GET /includes/api.php?action=get_results` - Get results
- `GET /includes/api.php?action=check_voting_status` - Check voting status

## Browser Compatibility

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Responsive Breakpoints

- Desktop: 1200px+
- Tablet: 768px - 1199px
- Mobile: < 768px

## Future Enhancements

- Email notifications
- OTP verification
- Two-factor authentication
- Audit logging
- Vote analytics dashboard
- Multi-language support
- Biometric authentication

## License

This project is open source and available for educational purposes.

## Support

For issues or questions, please refer to the documentation or create an issue in the repository.

---

**Last Updated**: 2024
**Version**: 1.0
