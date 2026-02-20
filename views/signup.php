<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - QCU Asset Management</title>
  <link rel="stylesheet" href="../styles/global.css">
  <link rel="stylesheet" href="../styles/signup.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
    <!-- Left side -->
    <div class="left-panel">
      <div class="logo">
        <img src="../assets/img/mainlogo.png" alt="QCU Logo">
      </div>
      <h1>Join Comprehensive Asset Management<br>For Quezon City University</h1>
    </div>

    <!-- Right side -->
    <div class="right-panel">
      <h2>Create Your Account</h2>

      <div id="signup-alert" class="alert" style="display:none;"></div>

  <form action="/1QCUPROJECT/backend/controllers/AuthController.php" method="POST" id="signup-form" class="signup-form" autocomplete="off">

      <input type="hidden" name="form_action" value="register">

      <div class="grid-2x2">
          <div style="grid-column: 1 / -1">
              <label for="full_name">Full Name</label>
              <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
          </div>

          <div style="grid-column: 1 /-1">
              <label for="email">Email address</label>
              <input type="email" id="email" name="email" placeholder="Enter your email" autocomplete="off" required>
          </div>

          <div>
              <label for="department">Department</label>
              <select id="department" name="department" required>
                  <option value="" disabled selected>Select your department</option>
                  <option value="IT">Information Technology</option>
                  <option value="CS">Computer Science</option>
                  <option value="HR">Human Resources</option>
                  <option value="Finance">Finance</option>
                  <option value="Admin">Administration</option>
                  <option value="Library">Library</option>
                  <option value="Others">Others</option>
              </select>
          </div>

          <div>
              <label for="employee_id">Employee/Staff ID</label>
              <input type="text" id="employee_id" name="employee_id" placeholder="Enter your employee/staff ID" required>
          </div>
      </div>

      <!-- Password fields -->
      <label for="password-label">Password</label>
      <div class="password-field">
          <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" required>
          <i class="fa-solid fa-eye toggle-icon" onclick="togglePassword('password', this)"></i>
      </div>

      <label for="password-confirmation">Confirm Password</label>
      <div class="password-field">
          <input type="password" id="password-confirmation" name="password_confirmation" placeholder="Re-enter your password" autocomplete="new-password" required>
          <i class="fa-solid fa-eye toggle-icon" onclick="togglePassword('password-confirmation', this)"></i>
      </div>

      <button type="submit" class="btn" style="margin-top:15px;">Send OTP</button>

      <div class="login-link" style="margin-top:10px;">
          Already have an account? <a href="login.php">Login here</a>
      </div>
  </form>

<script src="../scripts/signup.js"></script>
</body>
</html>
