console.log("login.js Loaded"); // Debug

// Pasword visibility toggle

function togglePassword(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Message Pop Up

document.addEventListener("DOMContentLoaded", function() {
    const alertBox = document.getElementById('login-alert');
    if (alertBox) {
        // Show alert
        alertBox.classList.add('show');

        // Hide after 3 seconds
        setTimeout(() => {
            alertBox.classList.remove('show');
        }, 3000);
    }
});

