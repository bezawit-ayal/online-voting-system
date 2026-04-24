# 🗳️ Online Voting System

A secure, modern, and user-friendly online voting platform built using **PHP, MySQL, JavaScript, HTML, and CSS**.  
The system enables users to vote electronically with strong security, real-time results, and an administrative control panel.

---

## 🚀 Features

### 👤 User Features
- Secure user registration and login system  
- Password hashing (bcrypt) for security  
- One-person-one-vote enforcement  
- View candidates and cast votes  
- Real-time election results  
- User profile management  

### 🛠️ Admin Features
- Admin dashboard with full control  
- Add, edit, and delete candidates  
- Manage users and elections  
- Start and end voting sessions  
- View voting statistics and results  

### 📊 System Features
- Real-time vote counting  
- Responsive and mobile-friendly design  
- Session-based authentication  
- Input validation and sanitization  
- SQL injection prevention using prepared statements  

---

## 🏗️ Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+)  
- **Backend:** PHP 7+ (PDO)  
- **Database:** MySQL  
- **Security:** bcrypt password hashing, session management  
- **UI:** Responsive design with animations  

---

## 📁 Project Structure

```text
online-voting-system/
│
├── index.html              # Landing page
├── login.php               # Login page
├── register.php            # Registration page
├── dashboard.php           # User dashboard
├── vote.php                # Voting page
├── results.php             # Results page
├── profile.php             # User profile
├── admin.php               # Admin panel
│
├── css/
│   └── styles.css          # Main stylesheet
│
├── js/
│   ├── auth.js             # Authentication logic
│   ├── vote.js             # Voting logic
│   ├── results.js          # Results handling
│   └── admin.js            # Admin functionality
│
├── includes/
│   ├── db.php              # Database connection
│   └── auth.php            # Authentication functions
│
├── api/
│   ├── auth_api.php        # Authentication API
│   ├── vote_api.php        # Voting API
│   ├── results_api.php     # Results API
│   └── admin_api.php       # Admin API
│
└── schema.sql              # Database schema
