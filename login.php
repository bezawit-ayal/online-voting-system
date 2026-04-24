<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Online Voting System</title>
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
        <a href="login.php" class="active">Login</a>
        <a href="register.php">Register</a>
      </div>
    </div>
  </nav>

  <div class="auth-container">
    <div class="auth-card slide-in">
      <div class="auth-header">
        <i class="fas fa-sign-in-alt"></i>
        <h1>Welcome Back</h1>
        <p>Sign in to your account to access the voting system</p>
      </div>

      <form id="loginForm" class="auth-form">
        <div class="form-group">
          <label for="username">
            <i class="fas fa-at"></i>
            Username or Email
          </label>
          <input type="text" id="username" name="username" required>
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

        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox" name="remember">
            <span class="checkmark"></span>
            Remember me
          </label>
        </div>

        <button type="submit" class="btn primary full-width">
          <i class="fas fa-sign-in-alt"></i>
          Sign In
        </button>
      </form>

      <div class="auth-footer">
        <p>Don't have an account? <a href="register.php">Create one here</a></p>
        <p><a href="#" class="forgot-password">Forgot your password?</a></p>
        <p class="auth-helper">Admin login (default seed): <strong>admin@voting.com</strong> / <strong>password</strong></p>
      </div>
    </div>
  </div>

  <script src="js/auth.js"></script>
</body>
</html>