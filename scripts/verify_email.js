console.log("verify_email.js Loaded");

document.addEventListener("DOMContentLoaded", function() {

    const form = document.querySelector("form[name='verify_otp']");
    const resendForm = document.querySelector("form[name='resend_otp']");

    console.log('verify form:', form);
    console.log('resend form:', resendForm);

    // ALERT DIV
    let alertDiv = document.getElementById('verify-alert');
    if (!alertDiv) {
        alertDiv = document.createElement('div');
        alertDiv.id = 'verify-alert';
        alertDiv.className = 'alert';
        alertDiv.style.display = 'none';
        form.parentNode.insertBefore(alertDiv, form);
    }

    function showAlert(message, duration = 3000) {
        alertDiv.textContent = message;
        alertDiv.style.display = 'block';
        alertDiv.classList.add('show');
        setTimeout(() => {
            alertDiv.style.display = 'none';
            alertDiv.classList.remove('show');
        }, duration);
    }

    // SUBMIT OTP
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        formData.set('form_action', 'verify_otp'); // ensure action

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await res.json();

            if (data.success) {
                showAlert(data.message || "Account verified!", 2000);
                setTimeout(() => {
                    window.location.href = data.redirect || "/1QCUPROJECT/views/login.php";
                }, 2000);
            } else {
                const messages = {
                    invalid_otp: "Incorrect OTP. Please try again.",
                    no_session: "Session expired. Please login again."
                };
                showAlert(messages[data.error] || "Unknown error. Try again.");
            }

        } catch (err) {
            showAlert("Server error. Please try again.");
            console.error(err);
        }
    });

    // RESEND OTP
    resendForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(resendForm); // <-- use this variable
        formData.set('form_action', 'resend_otp');  // <-- not resendFormData

        try {
            const res = await fetch(resendForm.action, { // <-- use resendForm.action
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await res.json();
            if (data.success) {
                showAlert(data.message || "OTP sent!");
            } else {
                showAlert("Failed to resend OTP. Try again.");
            }
        } catch (err) {
            showAlert("Server error. Please try again.");
            console.error(err);
        }
    });

});