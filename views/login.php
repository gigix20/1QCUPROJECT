
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Comprehensive Asset Management - QCU</title>
    <link rel="stylesheet" href="../styles/login.css" />
    <link rel="stylesheet" href="../styles/global.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
  </head>
  <body>
    <!-- Left side -->
    <div class="left-panel">
      <div class="logo">
        <img src="../assets/img/mainlogo.png" alt="QCU Logo" />
      </div>
      <h1>
        Unified Asset Management and Inventory System For Quezon City University
      </h1>
    </div>

    <!-- Right side -->
    <div class="right-panel">
      <h2>1QCU Inventory</h2>

    <?php if (isset($_GET['error'])): ?>
      <div id="login-alert" class="alert">
        <?php
          switch($_GET['error']) {
            case 'empty':
              echo "Please enter both email and password.";
              break;
            case 'invalid':
              echo "Incorrect email or password.";
              break;
            case 'unverified':
              echo "Your account is not verified. Check your email for the OTP.";
              break;
          }
        ?>
      </div>
    <?php endif; ?>

      <form
        id="login-form"
        action="/1QCUPROJECT/backend/controllers/LoginController.php"
        method="POST"
      >
        <div class="form-group">
          <label for="email">Email address</label>
          <input
            type="email"
            id="email"
            name="email"
            class="email-input"
            placeholder="Enter your email"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-field">
            <input
              type="password"
              id="password"
              name="password"
              class="password-input"
              placeholder="Enter your password"
              required
            />
            <i
              class="fa-solid fa-eye toggle-icon"
              onclick="togglePassword('password', this)"
            ></i>
          </div>
        </div>

        <div class="options">
          <label><input type="checkbox" /> Keep me logged in</label>
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit">Log In</button>
        <p class="signup">
          Don't have an account? <a href="signup.php">Create one</a>
        </p>
      </form>

      <script src="../scripts/login.js"></script>
    </div>
  </body>
</html>
