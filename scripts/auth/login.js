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

// Form submission and pop up messge

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("login-form");
  const alertDiv = document.getElementById("login-alert");

  if (!form || !alertDiv) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const res = await fetch(form.action, {
      method: "POST",
      body: formData,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    const data = await res.json();

    if (data.success) {
      window.location.href = data.redirect;
    } else {
      const messages = {
        empty: "Please enter both email and password.",
        invalid: "Incorrect email or password.",
        unverified:
          "Please verify your email to continue.",
        invalid_request: "Invalid request.",
      };

      alertDiv.textContent = messages[data.error] || "Unknown error occurred.";
      alertDiv.style.display = "block";
      alertDiv.classList.remove("show");
      void alertDiv.offsetWidth;
      alertDiv.classList.add("show");

      // hide after 3 seconds
      setTimeout(() => {
        alertDiv.classList.remove("show");

        if (data.error === "unverified") {
          window.location.href = "/1QCUPROJECT/views/auth/verify_email.php";
        }
      }, 3000);
    }
  });
});
