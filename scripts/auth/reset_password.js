console.log("Reset password js loaded")

function togglePw(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.style.color = isHidden ? 'var(--cyan)' : '';
}

const pwInput = document.getElementById('new_password');
const bar = document.getElementById('strengthBar');
const label = document.getElementById('strengthLabel');

pwInput.addEventListener('input', () => {
    const v = pwInput.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const levels = [
        { w: '0%',   bg: 'transparent', text: 'Password must be minimum of 8 characters' },
        { w: '25%',  bg: '#ff4d4d',      text: 'Weak' },
        { w: '50%',  bg: '#ffaa00',      text: 'Fair' },
        { w: '75%',  bg: '#00ccaa',      text: 'Good' },
        { w: '100%', bg: '#00ffff',      text: 'Strong' },
    ];

    const lvl = v.length === 0 ? levels[0] : levels[score] || levels[1];
    bar.style.width = lvl.w;
    bar.style.background = lvl.bg;
    label.textContent = lvl.text;
    label.style.color = lvl.bg;
});

// Password strength is not fully functional yet.