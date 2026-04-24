<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Online Voting System</title>
  <link rel="stylesheet" href="css/styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.html" class="nav-logo">
        <i class="fas fa-vote-yea"></i>
        VoteSecure
      </a>
      <div class="nav-links">
        <a href="index.html">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php" class="active">Register</a>
      </div>
    </div>
  </nav>

  <div class="auth-container">
    <div class="auth-card slide-in">
      <div class="auth-header">
        <i class="fas fa-user-plus"></i>
        <h1>Join Our Democratic Community</h1>
        <p>Create your account to participate in secure online voting</p>
      </div>

      <form id="registerForm" class="auth-form">
        <div class="form-group">
          <label for="fullName">
            <i class="fas fa-user"></i>
            Full Name
          </label>
          <input type="text" id="fullName" name="full_name" required>
          <div class="input-error"></div>
        </div>

        <div class="form-group">
          <label for="username">
            <i class="fas fa-at"></i>
            Username
          </label>
          <input type="text" id="username" name="username" required>
          <div class="input-error"></div>
        </div>

        <div class="form-group">
          <label for="email">
            <i class="fas fa-envelope"></i>
            Email Address
          </label>
          <input type="email" id="email" name="email" required>
          <div class="input-error"></div>
        </div>

        <div class="form-group">
          <label for="password">
            <i class="fas fa-lock"></i>
            Password
          </label>
          <input type="password" id="password" name="password" required>
          <div class="input-error"></div>
        </div>

        <div class="form-group">
          <label for="confirmPassword">
            <i class="fas fa-lock"></i>
            Confirm Password
          </label>
          <input type="password" id="confirmPassword" name="confirm_password" required>
          <div class="input-error"></div>
        </div>

        <button type="submit" class="btn primary full-width">
          <i class="fas fa-user-plus"></i>
          Create Account
        </button>
      </form>

      <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Sign in here</a></p>
      </div>
    </div>
  </div>

  <script src="js/auth.js"></script>
</body>
</html>