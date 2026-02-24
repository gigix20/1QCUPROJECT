const resendBtn = document.getElementById('resendBtn');
const resendHint = document.getElementById('resendHint');
let cooldown = 0;

console.log("verify_reset_otp script loaded")

resendBtn.addEventListener('click', () => {
    if (cooldown > 0) return;
    cooldown = 60;
    resendBtn.disabled = true;
    resendHint.classList.add('active');
    const timer = setInterval(() => {
        cooldown--;
        resendHint.textContent = `Resend available in ${cooldown}s`;
        if (cooldown <= 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            resendHint.textContent = '';
            resendHint.classList.remove('active');
        }
    }, 1000);
    resendHint.textContent = `Resend available in ${cooldown}s`;
    // Trigger backend resend here if needed
});