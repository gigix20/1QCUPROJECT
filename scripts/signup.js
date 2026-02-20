console.log("signup.js Loaded");

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("signup-form");
    const alertDiv = document.getElementById("signup-alert");

    // Password toggle
    window.togglePassword = function (fieldId, icon) {
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
    };

    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect;
                return;
            }

            // Show error message
            const messages = {
                empty: "Please fill in all required fields.",
                password_mismatch: "Passwords do not match.",
                exists: "Email or Employee ID already exists.",
                server_error: "Server error. Please try again."
            };

            alertDiv.style.display = "block";
            alertDiv.textContent = messages[data.error] || "Something went wrong.";
            alertDiv.classList.add("show");

            // Auto hide after 3 seconds
            setTimeout(() => {
                alertDiv.classList.remove("show");
            }, 3000);

        } catch (error) {
            console.error("Signup error:", error);

            alertDiv.style.display = "block";
            alertDiv.textContent = "Server error. Please try again.";
            alertDiv.classList.add("show");
        }
    });
});