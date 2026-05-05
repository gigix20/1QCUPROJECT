// Notification System JavaScript
class NotificationManager {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.notificationDropdown = null;
        this.notificationBadge = null;
        this.init();
    }

    init() {
        this.createNotificationUI();
        this.loadNotifications();
        this.setupEventListeners();
        this.startPeriodicCheck();
    }

    createNotificationUI() {
        // Create notification dropdown in the header
        const header = document.querySelector('.header') || document.querySelector('header');
        if (!header) return;

        const notificationHTML = `
            <div class="notification-container">
                <button class="notification-btn" id="notificationBtn">
                    <span class="notification-icon">🔔</span>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                    <div class="notification-header">
                        <h4>Notifications</h4>
                        <button class="mark-all-read-btn" id="markAllReadBtn">Mark All Read</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="notification-empty">No notifications</div>
                    </div>
                </div>
            </div>
        `;

        header.insertAdjacentHTML('beforeend', notificationHTML);

        this.notificationDropdown = document.getElementById('notificationDropdown');
        this.notificationBadge = document.getElementById('notificationBadge');
    }

    setupEventListeners() {
        // Toggle dropdown
        document.getElementById('notificationBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

        // Mark all as read
        document.getElementById('markAllReadBtn')?.addEventListener('click', () => {
            this.markAllAsRead();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notification-container')) {
                this.hideDropdown();
            }
        });
    }

    toggleDropdown() {
        if (this.notificationDropdown.style.display === 'none') {
            this.showDropdown();
        } else {
            this.hideDropdown();
        }
    }

    showDropdown() {
        this.notificationDropdown.style.display = 'block';
        this.loadNotifications();
    }

    hideDropdown() {
        this.notificationDropdown.style.display = 'none';
    }

    async loadNotifications() {
        try {
            const response = await fetch('/1QCUPROJECT/backend/routes/notifications_route.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.notifications = data.data.notifications || [];
                this.unreadCount = data.data.unread_count || 0;
                this.updateUI();
            } else {
                console.error('Notifications API error:', data);
            }
        } catch (error) {
            console.error('Error loading notifications - Full details:', error);
        }
    }

    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);

            const response = await fetch('/1QCUPROJECT/backend/routes/notifications_route.php?action=mark_read', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/1QCUPROJECT/backend/routes/notifications_route.php?action=mark_all_read', {
                method: 'POST'
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.loadNotifications();
                showToast('All notifications marked as read');
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    updateUI() {
        this.updateBadge();
        this.updateNotificationList();
    }

    updateBadge() {
        if (this.unreadCount > 0) {
            this.notificationBadge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            this.notificationBadge.style.display = 'inline';
        } else {
            this.notificationBadge.style.display = 'none';
        }
    }

    updateNotificationList() {
        const list = document.getElementById('notificationList');
        if (!list) return;

        if (this.notifications.length === 0) {
            list.innerHTML = '<div class="notification-empty">No notifications</div>';
            return;
        }

        const html = this.notifications.map(notification => `
            <div class="notification-item ${notification.IS_READ == 0 ? 'unread' : ''} ${notification.TYPE}" data-id="${notification.NOTIFICATION_ID}">
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHtml(notification.TITLE)}</div>
                    <div class="notification-message">${this.escapeHtml(notification.MESSAGE)}</div>
                    <div class="notification-time">${this.formatTime(notification.CREATED_AT)}</div>
                </div>
                ${notification.IS_READ == 0 ? '<div class="notification-unread-dot"></div>' : ''}
            </div>
        `).join('');

        list.innerHTML = html;

        // Add click handlers
        list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = item.dataset.id;
                const notification = this.notifications.find(n => n.NOTIFICATION_ID == notificationId);

                if (notification && notification.IS_READ == 0) {
                    this.markAsRead(notificationId);
                }

                if (notification.ACTION_URL) {
                    window.location.href = notification.ACTION_URL;
                }
            });
        });
    }

    startPeriodicCheck() {
        // Check for new notifications every 30 seconds
        setInterval(() => {
            this.checkForNewNotifications();
        }, 30000);
    }

    async checkForNewNotifications() {
        try {
            const response = await fetch('/1QCUPROJECT/backend/routes/notifications_route.php?action=unread_count');
            const data = await response.json();

            if (data.status === 'success' && data.data.unread_count !== this.unreadCount) {
                this.unreadCount = data.data.unread_count;
                this.updateBadge();

                // If dropdown is open, refresh the list
                if (this.notificationDropdown.style.display !== 'none') {
                    this.loadNotifications();
                }
            } else if (data.status !== 'success') {
                console.error('Check notifications API error:', data);
            }
        } catch (error) {
            console.error('Error checking for new notifications - Full details:', error);
        }
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;

        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});

// Global function to show toast messages
function showToast(message, type = 'info') {
    // Remove existing toast
    const existingToast = document.getElementById('toast');
    if (existingToast) {
        existingToast.remove();
    }

    // Create new toast
    const toast = document.createElement('div');
    toast.id = 'toast';
    toast.className = `toast ${type}`;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);

    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}