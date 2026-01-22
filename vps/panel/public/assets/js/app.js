/**
 * VPS Panel - JavaScript
 */

// Notification system
function showNotification(message, type = 'info') {
    const container = document.getElementById('notifications') || createNotificationContainer();
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('notification-fade');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notifications';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    `;
    document.body.appendChild(container);
    return container;
}

// Add notification styles
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification {
        padding: 12px 16px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        animation: slideIn 0.3s ease;
    }
    
    .notification-success {
        background: rgba(35, 134, 54, 0.95);
        color: white;
    }
    
    .notification-error {
        background: rgba(248, 81, 73, 0.95);
        color: white;
    }
    
    .notification-info {
        background: rgba(88, 166, 255, 0.95);
        color: white;
    }
    
    .notification-warning {
        background: rgba(210, 153, 34, 0.95);
        color: white;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: inherit;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: 0.7;
    }
    
    .notification-close:hover {
        opacity: 1;
    }
    
    .notification-fade {
        animation: slideOut 0.3s ease forwards;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(notificationStyles);

// Confirm dangerous actions
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
        const message = el.dataset.confirm || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
});

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
});

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

// Add mobile menu button if needed
if (window.innerWidth <= 768) {
    const menuBtn = document.createElement('button');
    menuBtn.className = 'mobile-menu-btn';
    menuBtn.innerHTML = '☰';
    menuBtn.style.cssText = `
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1000;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 1.2rem;
        cursor: pointer;
    `;
    menuBtn.addEventListener('click', toggleSidebar);
    document.body.appendChild(menuBtn);
}

// Form validation
document.querySelectorAll('form.form').forEach(form => {
    form.addEventListener('submit', (e) => {
        const requiredFields = form.querySelectorAll('[required]');
        let valid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = 'var(--error)';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!valid) {
            e.preventDefault();
            showNotification('Please fill in all required fields', 'error');
        }
    });
});

// Loading state for buttons
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Loading...';
            
            // Reset after 10 seconds (fallback)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Escape to close modals
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
    
    // Ctrl+Enter to submit forms
    if (e.ctrlKey && e.key === 'Enter') {
        const activeElement = document.activeElement;
        if (activeElement && activeElement.form) {
            activeElement.form.submit();
        }
    }
});

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard', 'success');
    }).catch(() => {
        showNotification('Failed to copy', 'error');
    });
}

// Make code blocks clickable to copy
document.querySelectorAll('code').forEach(code => {
    code.style.cursor = 'pointer';
    code.title = 'Click to copy';
    code.addEventListener('click', () => {
        copyToClipboard(code.textContent);
    });
});

// Relative time formatting
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 0) return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    if (minutes > 0) return `${minutes}m ago`;
    return 'Just now';
}

// Update relative times on page
document.querySelectorAll('.date-relative').forEach(el => {
    const date = el.getAttribute('title');
    if (date) {
        el.textContent = formatRelativeTime(date);
    }
});

// Auto-refresh stats on dashboard
if (document.querySelector('.stats-grid')) {
    setInterval(async () => {
        try {
            const response = await fetch('/api/stats');
            const stats = await response.json();
            
            // Update stats display if elements exist
            // This would need specific element IDs to update
        } catch (err) {
            console.error('Failed to refresh stats');
        }
    }, 60000); // Every minute
}

console.log('VPS Panel loaded');
