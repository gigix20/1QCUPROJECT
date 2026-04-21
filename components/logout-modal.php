<style>
#logoutModal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 9999;
  align-items: center;
  justify-content: center;
}

#logoutModal.active {
  display: flex;
}

#logoutModal .logout-modal-box {
  background: white;
  width: 380px;
  max-width: 95vw;
  border-radius: 14px;
  padding: 30px 32px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  animation: logoutSlideUp 0.25s ease;
}

@keyframes logoutSlideUp {
  from { transform: translateY(30px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

#logoutModal .logout-modal-title {
  font-size: 22px;
  font-weight: 500;
  color: #1a1a2e;
  margin-bottom: 12px;
  font-style: italic;
  font-family: "Outfit", sans-serif;
}

#logoutModal .logout-modal-divider {
  height: 2px;
  background: linear-gradient(to right, #7c3aed, transparent);
  margin-bottom: 18px;
}

#logoutModal .logout-modal-label {
  font-size: 11px;
  font-weight: 600;
  color: #888;
  letter-spacing: 0.8px;
  margin-bottom: 12px;
  font-family: "Outfit", sans-serif;
}

#logoutModal .logout-modal-message {
  font-size: 13.5px;
  color: #333;
  font-family: "Outfit", sans-serif;
  margin-bottom: 24px;
  line-height: 1.5;
}

#logoutModal .logout-modal-buttons {
  display: flex;
  justify-content: center;
  gap: 16px;
}

#logoutModal .logout-cancel-btn {
  padding: 10px 36px;
  background: #e5e7eb;
  color: #333;
  border: none;
  border-radius: 8px;
  font-family: "Outfit", sans-serif;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 1px;
  cursor: pointer;
  transition: background 0.2s;
}

#logoutModal .logout-cancel-btn:hover {
  background: #d1d5db;
}

#logoutModal .logout-confirm-btn {
  padding: 10px 36px;
  background: #5b21b6;
  color: white;
  border: none;
  border-radius: 8px;
  font-family: "Outfit", sans-serif;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 1px;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  transition: background 0.2s;
}

#logoutModal .logout-confirm-btn:hover {
  background: #7c3aed;
}
</style>

<div id="logoutModal">
  <div class="logout-modal-box">
    <div class="logout-modal-title">Log Out</div>
    <div class="logout-modal-divider"></div>
    <div class="logout-modal-label">CONFIRM ACTION</div>
    <p class="logout-modal-message">Are you sure you want to log out of your session?</p>
    <div class="logout-modal-buttons">
      <button class="logout-cancel-btn" onclick="closeLogoutModal()">CANCEL</button>
      <a href="/1QCUPROJECT/backend/controllers/LogoutController.php" class="logout-confirm-btn">LOG OUT</a>
    </div>
  </div>
</div>

<script>
function openLogoutModal() {
  document.getElementById('logoutModal').classList.add('active');
}

function closeLogoutModal() {
  document.getElementById('logoutModal').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('logoutModal').addEventListener('click', function (e) {
    if (e.target === this) closeLogoutModal();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLogoutModal();
  });
});
</script>