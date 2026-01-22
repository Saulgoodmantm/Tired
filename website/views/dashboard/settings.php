<?php
/**
 * Dashboard - Settings
 */
$activePage = 'settings';
$pageTitle = 'Settings';

use App\Utils\Auth;
use App\Services\CalendarService;

$calendarService = new CalendarService();
$calendarConnected = $calendarService->isConnected();
?>

<!-- Settings Navigation -->
<div class="settings-nav mb-xl">
    <button class="settings-tab active" data-tab="general">General</button>
    <button class="settings-tab" data-tab="branding">Branding</button>
    <button class="settings-tab" data-tab="integrations">Integrations</button>
    <button class="settings-tab" data-tab="notifications">Notifications</button>
    <button class="settings-tab" data-tab="security">Security</button>
</div>

<!-- General Settings -->
<div class="settings-panel active" id="panel-general">
    <div class="card dashboard-card">
        <div class="card-header">
            <h3>General Settings</h3>
        </div>
        <div class="card-body">
            <form id="general-settings">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="site_name" class="form-input" 
                           value="<?= htmlspecialchars($settings['site_name'] ?? 'TiredOfDoinTM') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="tagline" class="form-input" 
                           value="<?= htmlspecialchars($settings['tagline'] ?? 'Professional Photography') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-input" 
                           value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-select">
                        <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>Eastern Time (ET)</option>
                        <option value="America/Chicago" <?= ($settings['timezone'] ?? '') === 'America/Chicago' ? 'selected' : '' ?>>Central Time (CT)</option>
                        <option value="America/Denver" <?= ($settings['timezone'] ?? '') === 'America/Denver' ? 'selected' : '' ?>>Mountain Time (MT)</option>
                        <option value="America/Los_Angeles" <?= ($settings['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : '' ?>>Pacific Time (PT)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Gate Password</label>
                    <input type="text" name="gate_password" class="form-input" 
                           value="<?= htmlspecialchars($settings['gate_password'] ?? '67') ?>"
                           placeholder="Password to access the site">
                    <p class="form-hint">Users must enter this password to access the site</p>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<!-- Branding Settings -->
<div class="settings-panel" id="panel-branding">
    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Branding</h3>
        </div>
        <div class="card-body">
            <form id="branding-settings">
                <div class="form-group">
                    <label class="form-label">Logo</label>
                    <div class="file-upload-area" id="logo-upload">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Drop logo here or click to upload</p>
                        <input type="file" name="logo" accept="image/*" hidden>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Primary Color</label>
                    <div class="flex items-center gap-md">
                        <input type="color" name="primary_color" class="form-input-color" 
                               value="<?= $settings['primary_color'] ?? '#7c6bf0' ?>">
                        <input type="text" name="primary_color_hex" class="form-input" style="width: 120px;"
                               value="<?= $settings['primary_color'] ?? '#7c6bf0' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Social Media Links</label>
                    <div class="grid gap-md" style="max-width: 400px;">
                        <input type="url" name="instagram" class="form-input" placeholder="Instagram URL"
                               value="<?= htmlspecialchars($settings['social']['instagram'] ?? '') ?>">
                        <input type="url" name="twitter" class="form-input" placeholder="Twitter/X URL"
                               value="<?= htmlspecialchars($settings['social']['twitter'] ?? '') ?>">
                        <input type="url" name="tiktok" class="form-input" placeholder="TikTok URL"
                               value="<?= htmlspecialchars($settings['social']['tiktok'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<!-- Integrations Settings -->
<div class="settings-panel" id="panel-integrations">
    <div class="card dashboard-card mb-lg">
        <div class="card-header">
            <h3>Google Calendar</h3>
            <?php if ($calendarConnected): ?>
                <span class="badge badge-success">Connected</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <p class="text-secondary mb-lg">
                Connect Google Calendar to automatically sync bookings and manage availability.
            </p>
            
            <?php if ($calendarConnected): ?>
                <button class="btn btn-ghost" onclick="disconnectCalendar()">Disconnect Calendar</button>
            <?php else: ?>
                <a href="<?= $calendarService->getAuthUrl() ?>" class="btn btn-primary">
                    Connect Google Calendar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card dashboard-card mb-lg">
        <div class="card-header">
            <h3>Stripe</h3>
            <span class="badge badge-success">Connected</span>
        </div>
        <div class="card-body">
            <p class="text-secondary mb-lg">
                Stripe is configured for payment processing.
            </p>
            <a href="https://dashboard.stripe.com" target="_blank" class="btn btn-ghost">
                Open Stripe Dashboard
            </a>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Cloudflare R2</h3>
            <span class="badge badge-success">Connected</span>
        </div>
        <div class="card-body">
            <p class="text-secondary mb-lg">
                Cloudflare R2 is configured for image storage.
            </p>
            <p class="text-muted text-sm">Bucket: tiredproduction</p>
        </div>
    </div>
</div>

<!-- Notifications Settings -->
<div class="settings-panel" id="panel-notifications">
    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Email Notifications</h3>
        </div>
        <div class="card-body">
            <form id="notification-settings">
                <div class="notification-option">
                    <div>
                        <strong>New Booking</strong>
                        <p class="text-muted text-sm">Get notified when a new booking is made</p>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" name="notify_booking" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-option">
                    <div>
                        <strong>Payment Received</strong>
                        <p class="text-muted text-sm">Get notified when a payment is received</p>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" name="notify_payment" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-option">
                    <div>
                        <strong>Contract Signed</strong>
                        <p class="text-muted text-sm">Get notified when a contract is signed</p>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" name="notify_contract" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-option">
                    <div>
                        <strong>Contact Form</strong>
                        <p class="text-muted text-sm">Get notified on new contact submissions</p>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" name="notify_contact" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary mt-lg">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<!-- Security Settings -->
<div class="settings-panel" id="panel-security">
    <div class="card dashboard-card mb-lg">
        <div class="card-header">
            <h3>Admin Accounts</h3>
        </div>
        <div class="card-body">
            <p class="text-secondary mb-lg">
                Users with admin access to the dashboard.
            </p>
            <ul class="admin-list">
                <li>maxudelep@gmail.com <span class="badge badge-info">Owner</span></li>
                <li>taeveyrust@gmail.com <span class="badge badge-info">Admin</span></li>
                <li>goshakucherenkobeast@gmail.com <span class="badge badge-info">Admin</span></li>
                <li>lana8186296366@gmail.com <span class="badge badge-info">Admin</span></li>
                <li>justforvalorant41@gmail.com <span class="badge badge-info">Admin</span></li>
            </ul>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Danger Zone</h3>
        </div>
        <div class="card-body">
            <div class="danger-action">
                <div>
                    <strong>Clear Cache</strong>
                    <p class="text-muted text-sm">Clear all cached data</p>
                </div>
                <button class="btn btn-ghost" onclick="clearCache()">Clear Cache</button>
            </div>

            <div class="danger-action">
                <div>
                    <strong>Reset Analytics</strong>
                    <p class="text-muted text-sm">Clear all analytics data</p>
                </div>
                <button class="btn btn-ghost text-error" onclick="resetAnalytics()">Reset</button>
            </div>
        </div>
    </div>
</div>

<style>
.settings-nav {
    display: flex;
    gap: var(--space-xs);
    border-bottom: 1px solid var(--bg-hover);
    padding-bottom: var(--space-md);
    overflow-x: auto;
}

.settings-tab {
    padding: var(--space-sm) var(--space-lg);
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 0.9375rem;
    cursor: pointer;
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
    white-space: nowrap;
}

.settings-tab:hover {
    color: var(--text-primary);
    background: var(--bg-hover);
}

.settings-tab.active {
    color: var(--violet-400);
    background: rgba(124, 107, 240, 0.1);
}

.settings-panel {
    display: none;
}

.settings-panel.active {
    display: block;
}

.file-upload-area {
    border: 2px dashed var(--bg-hover);
    border-radius: var(--radius-lg);
    padding: var(--space-xxl);
    text-align: center;
    color: var(--text-muted);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.file-upload-area:hover {
    border-color: var(--violet-500);
    color: var(--text-primary);
}

.form-input-color {
    width: 50px;
    height: 40px;
    padding: 0;
    border: none;
    cursor: pointer;
}

.notification-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-lg) 0;
    border-bottom: 1px solid var(--bg-hover);
}

.notification-option:last-of-type {
    border-bottom: none;
}

.toggle {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
}

.toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: var(--bg-hover);
    border-radius: 26px;
    transition: var(--transition-fast);
}

.toggle-slider::before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: var(--transition-fast);
}

.toggle input:checked + .toggle-slider {
    background: var(--violet-500);
}

.toggle input:checked + .toggle-slider::before {
    transform: translateX(22px);
}

.admin-list {
    list-style: none;
}

.admin-list li {
    padding: var(--space-sm) 0;
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.danger-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-lg) 0;
    border-bottom: 1px solid var(--bg-hover);
}

.danger-action:last-child {
    border-bottom: none;
}

.form-hint {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-top: var(--space-xs);
}
</style>

<script>
// Tab switching
document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
        
        tab.classList.add('active');
        document.getElementById(`panel-${tab.dataset.tab}`).classList.add('active');
    });
});

// File upload
document.getElementById('logo-upload')?.addEventListener('click', function() {
    this.querySelector('input[type="file"]').click();
});

// Form submissions
document.getElementById('general-settings')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    await saveSettings('general', new FormData(e.target));
});

document.getElementById('branding-settings')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    await saveSettings('branding', new FormData(e.target));
});

document.getElementById('notification-settings')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    await saveSettings('notifications', new FormData(e.target));
});

async function saveSettings(section, formData) {
    try {
        const response = await fetch(`/api/settings/${section}`, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            TODT?.utils?.notify?.('Settings saved', 'success') || alert('Settings saved');
        } else {
            throw new Error('Failed to save');
        }
    } catch (error) {
        console.error(error);
        TODT?.utils?.notify?.('Failed to save settings', 'error') || alert('Failed to save');
    }
}

async function disconnectCalendar() {
    if (!confirm('Disconnect Google Calendar?')) return;
    
    await fetch('/api/calendar/disconnect', { method: 'POST' });
    location.reload();
}

async function clearCache() {
    if (!confirm('Clear all cached data?')) return;
    
    await fetch('/api/cache/clear', { method: 'POST' });
    TODT?.utils?.notify?.('Cache cleared', 'success') || alert('Cache cleared');
}

async function resetAnalytics() {
    if (!confirm('This will delete ALL analytics data. Are you sure?')) return;
    if (!confirm('This action cannot be undone. Confirm again to proceed.')) return;
    
    await fetch('/api/analytics/reset', { method: 'POST' });
    location.reload();
}
</script>
