<?php
session_start();

if (!isset($_SESSION['forgot_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['reset_email'];

function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];

    return substr($name, 0, 2) 
        . str_repeat('*', max(strlen($name) - 2, 0)) 
        . '@' 
        . $domain;
}

$maskedEmail = maskEmail($email);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../styles/auth/reset_password.css">
    <link rel="stylesheet" href="../../styles/global.css">
</head>
<body>

    <div class="left-panel">
        <div class="icon-key">
            <svg viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="34" cy="38" r="16" stroke="#00ffff" stroke-width="2.5"/>
                <line x1="46" y1="48" x2="76" y2="72" stroke="#00ffff" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="65" y1="63" x2="65" y2="72" stroke="#00ffff" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="72" y1="68" x2="72" y2="76" stroke="#00ffff" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="34" cy="38" r="5" fill="#00ffff" opacity="0.7"/>
            </svg>
        </div>
        <h1>Set New Password</h1>
        <p>Choose a strong, unique password to protect your account going forward.</p>
    </div>

    <div class="right-panel">
        <h2>Reset Password</h2>

        <form class="reset-form" method="POST" action="../../backend/routes/reset_password_route.php">
            <p class="reset-email-hint">
                Creating a new password for:
                <strong><?php echo htmlspecialchars($maskedEmail ?? 'your email'); ?></strong>
            </p>

            <div class="input-wrapper">
                <label for="new_password">New Password</label>

                <div class="input-field-wrap">
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)" aria-label="Toggle password visibility">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>

                <div class="strength-bar-wrapper"><div class="strength-bar" id="strengthBar"></div></div>
                <div class="strength-label" id="strengthLabel"></div>

            </div>

            <div class="input-wrapper">
                <label for="confirm_password">Confirm Password</label>

                <div class="input-field-wrap">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)" aria-label="Toggle password visibility">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary">Reset Password</button>

            <?php
            if (isset($_SESSION['reset_msg'])) {
                echo '<p class="msg-box">' . htmlspecialchars($_SESSION['reset_msg']) . '</p>';
                unset($_SESSION['reset_msg']);
            }
            ?>
        </form>

        <div class="back-link">
            <a href="/1QCUPROJECT/views/auth/login.php">← Back to Login</a>
        </div>
    </div>

<script src="../../scripts/auth/reset_password.js"></script>

</body>
</html>