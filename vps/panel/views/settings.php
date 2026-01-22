<div class="page-header">
    <h1>Settings</h1>
</div>

<div class="settings-grid">
    <!-- General Settings -->
    <div class="card">
        <div class="card-header">
            <h2>General</h2>
        </div>
        <div class="card-body">
            <div class="settings-list">
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Application Name</strong>
                        <span class="text-muted"><?= htmlspecialchars($settings['app_name']) ?></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Panel URL</strong>
                        <span class="text-muted"><?= htmlspecialchars($settings['app_url']) ?></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Sites Directory</strong>
                        <code><?= htmlspecialchars($settings['sites_root']) ?></code>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Deploy User</strong>
                        <code><?= htmlspecialchars($settings['deploy_user']) ?></code>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Authentication -->
    <div class="card">
        <div class="card-header">
            <h2>Authentication</h2>
        </div>
        <div class="card-body">
            <div class="settings-list">
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Google OAuth</strong>
                        <span class="<?= $settings['google_configured'] ? 'text-success' : 'text-warning' ?>">
                            <?= $settings['google_configured'] ? '✓ Configured' : '⚠ Not Configured' ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <form action="/settings" method="POST" class="form settings-form">
                <div class="form-group">
                    <label for="allowed_emails">Allowed Emails</label>
                    <textarea id="allowed_emails" name="allowed_emails" class="form-input" rows="3"
                              placeholder="user1@gmail.com, user2@gmail.com"><?= htmlspecialchars($settings['allowed_emails']) ?></textarea>
                    <span class="form-hint">Comma-separated list of emails allowed to access the panel</span>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Current Session -->
    <div class="card">
        <div class="card-header">
            <h2>Current Session</h2>
        </div>
        <div class="card-body">
            <div class="settings-list">
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Logged in as</strong>
                        <span><?= htmlspecialchars($user['email'] ?? 'Unknown') ?></span>
                    </div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Login Time</strong>
                        <span><?= isset($_SESSION['login_time']) ? date('M j, Y H:i', $_SESSION['login_time']) : 'Unknown' ?></span>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="/logout" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Setup Instructions -->
    <div class="card">
        <div class="card-header">
            <h2>Google OAuth Setup</h2>
        </div>
        <div class="card-body">
            <ol class="setup-steps">
                <li>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></li>
                <li>Create a new project or select existing</li>
                <li>Go to "OAuth consent screen" and configure it</li>
                <li>Go to "Credentials" → "Create Credentials" → "OAuth client ID"</li>
                <li>Select "Web application"</li>
                <li>Add authorized redirect URI: <code><?= htmlspecialchars($settings['app_url']) ?>/auth/google/callback</code></li>
                <li>Copy Client ID and Secret to <code>config/.env</code></li>
            </ol>
            
            <div class="code-block">
                <code>
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com<br>
GOOGLE_CLIENT_SECRET=your-client-secret<br>
GOOGLE_REDIRECT_URI=<?= htmlspecialchars($settings['app_url']) ?>/auth/google/callback
                </code>
            </div>
        </div>
    </div>
</div>
