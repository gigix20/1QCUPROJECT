console.log("signup.js Loaded")

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

document.addEventListener("DOMContentLoaded", function() {
    const alertBox = document.getElementById('signup-alert');
    if (alertBox) {

        alertBox.classList.add('show');

        setTimeout(() => {
            alertBox.classList.remove('show');
        }, 3000);
    }
});




