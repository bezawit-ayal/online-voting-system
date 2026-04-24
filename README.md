# VoteSecure - Modern Online Voting System

A comprehensive, secure, and user-friendly online voting platform built with modern web technologies.

## Features

- **Secure Authentication**: User registration and login with password hashing
- **One-Person-One-Vote**: Prevents multiple voting with session-based validation
- **Real-time Results**: Live voting statistics and result visualization
- **Admin Panel**: Complete administrative control with user and candidate management
- **Responsive Design**: Modern UI with animations and mobile-friendly interface
- **Profile Management**: User profile editing and password change functionality

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Backend**: PHP 7+ with PDO
- **Database**: MySQL
- **Security**: Password hashing (bcrypt), session management, input validation

## Project Structure

```
online-voting-system/
├── index.html              # Landing page
├── css/
│   └── styles.css          # Main stylesheet
├── js/
│   ├── auth.js            # Authentication logic
│   ├── dashboard.js       # User dashboard functionality
│   ├── vote.js            # Voting interface logic
│   ├── results.js         # Results visualization
│   ├── profile.js         # Profile management
│   ├── admin.js           # Admin panel functionality
│   └── landing.js         # Landing page animations
├── includes/
│   ├── auth.php           # Authentication functions
│   └── db.php             # Database connection
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # User dashboard
├── vote.php               # Voting interface
├── results.php            # Results page
├── profile.php            # User profile
├── admin.php              # Admin panel
├── auth_api.php           # Authentication API
├── vote_api.php           # Voting API
├── results_api.php        # Results API
├── profile_api.php        # Profile API
├── admin_api.php          # Admin API
├── stats_api.php          # Statistics API
└── schema.sql             # Database schema
```

## Setup Instructions

### Prerequisites
- XAMPP (or similar Apache + MySQL + PHP stack)
- Web browser

### Installation

1. **Clone/Download the project** to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\online-voting-system\
   ```

2. **Start XAMPP** and ensure Apache and MySQL services are running.

3. **Create the database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `voting_system`
   - Import the `schema.sql` file to create tables and sample data

4. **Configure database connection** (if needed):
   - Edit `includes/db.php` to match your database credentials
   - Default settings work with XAMPP default configuration

5. **Access the application**:
   - Open your browser and go to: `http://localhost/online-voting-system/`
   - Register a new account or use existing sample data

### Sample Data

The `schema.sql` file includes sample users and candidates:
- **Admin User**: admin@example.com / password123
- **Regular Users**: user1@example.com, user2@example.com, etc. / password123
- **Candidates**: Sample candidates from different parties

## Usage

### For Voters
1. Register an account or login
2. View your dashboard with voting status
3. Cast your vote for your preferred candidate
4. View real-time election results
5. Manage your profile settings

### For Administrators
1. Login with admin credentials
2. Access the admin panel from the dashboard
3. Manage users (view, edit, delete)
4. Manage candidates (add, edit, remove)
5. View system statistics and election data

## Security Features

- Password hashing using bcrypt
- Session-based authentication
- Input validation and sanitization
- SQL injection prevention with prepared statements
- One-person-one-vote enforcement
- Admin role-based access control

## API Endpoints

- `POST /auth_api.php` - User authentication (login/register)
- `POST /vote_api.php` - Cast votes
- `GET /results_api.php` - Get election results
- `POST /profile_api.php` - Update user profile
- `GET/POST /admin_api.php` - Admin operations
- `GET /stats_api.php` - System statistics

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For issues or questions, please create an issue in the repository or contact the development team.
- Input sanitization and validation
- SQL injection protection with prepared statements

## Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 7+
- **Database**: MySQL
- **Styling**: Custom CSS with Bootstrap framework

## File Structure

```
online-voting-system/
├── index.php          # Home page
├── login.php          # User login
├── register.php       # User registration
├── vote.php           # Voting page
├── results.php        # Voting results
├── admin.php          # Admin panel
├── logout.php         # Logout script
├── css/
│   └── style.css      # Custom styles
├── js/
│   └── script.js      # JavaScript functionality
├── includes/
│   └── db.php         # Database connection
├── sql/
│   └── database.sql   # Database schema
└── README.md          # This file
```

## Troubleshooting

- Ensure Apache and MySQL are running in XAMPP.
- Check database credentials in `includes/db.php`.
- Make sure the database is imported correctly.
- Clear browser cache if experiencing issues.

## License

This project is for educational purposes. Modify and use at your own risk.