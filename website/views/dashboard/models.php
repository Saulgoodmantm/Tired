<?php
/**
 * Dashboard - Models Management
 * Manage models/talent for shoots
 */

$activePage = 'models';
$pageTitle = 'Models';

// Get models from database
$db = \App\Utils\Database::getInstance();
$models = $db->query("
    SELECT u.*, r.name as role_name,
           COUNT(DISTINCT b.id) as booking_count,
           COUNT(DISTINCT g.id) as gallery_count
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN booking_models bm ON bm.user_id = u.id
    LEFT JOIN bookings b ON b.id = bm.booking_id
    LEFT JOIN galleries g ON g.user_id = u.id
    WHERE r.name = 'model'
    GROUP BY u.id, r.name
    ORDER BY u.created_at DESC
")->fetchAll();

// Get all users who could be promoted to models
$potentialModels = $db->query("
    SELECT u.*, r.name as role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.name NOT IN ('model', 'admin')
    ORDER BY u.username ASC
")->fetchAll();
?>

<?php ob_start(); ?>

<div class="dashboard-header">
    <div class="dashboard-header-content">
        <h1>Models</h1>
        <p class="text-muted">Manage your talent roster</p>
    </div>
    <button class="btn btn-primary" onclick="openAddModelModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <line x1="20" y1="8" x2="20" y2="14"></line>
            <line x1="23" y1="11" x2="17" y2="11"></line>
        </svg>
        Add Model
    </button>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-value"><?= count($models) ?></div>
        <div class="stat-label">Total Models</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count(array_filter($models, fn($m) => $m['is_verified'])) ?></div>
        <div class="stat-label">Verified</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= array_sum(array_column($models, 'booking_count')) ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
</div>

<!-- Models Grid -->
<div class="models-grid">
    <?php if (empty($models)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <h3>No models yet</h3>
            <p>Add your first model to start building your roster</p>
            <button class="btn btn-primary" onclick="openAddModelModal()">Add Model</button>
        </div>
    <?php else: ?>
        <?php foreach ($models as $model): ?>
            <div class="model-card" data-id="<?= $model['id'] ?>">
                <div class="model-avatar">
                    <?php if ($model['avatar_url']): ?>
                        <img src="<?= htmlspecialchars($model['avatar_url']) ?>" alt="<?= htmlspecialchars($model['username']) ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($model['username'] ?? $model['email'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($model['is_verified']): ?>
                        <div class="verified-badge" title="Verified">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="model-info">
                    <h3><?= htmlspecialchars($model['username'] ?? 'Unnamed') ?></h3>
                    <p class="model-email"><?= htmlspecialchars($model['email']) ?></p>
                    <div class="model-stats">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                            </svg>
                            <?= $model['booking_count'] ?> shoots
                        </span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <?= $model['gallery_count'] ?> galleries
                        </span>
                    </div>
                </div>
                <div class="model-actions">
                    <button class="btn btn-sm btn-ghost" onclick="viewModelProfile(<?= $model['id'] ?>)" title="View Profile">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-ghost" onclick="editModel(<?= $model['id'] ?>)" title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-ghost text-danger" onclick="removeModel(<?= $model['id'] ?>)" title="Remove">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="18" y1="8" x2="23" y2="13"></line>
                            <line x1="23" y1="8" x2="18" y2="13"></line>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Model Modal -->
<div id="addModelModal" class="modal">
    <div class="modal-backdrop" onclick="closeAddModelModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Model</h2>
            <button class="modal-close" onclick="closeAddModelModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('existing')">Existing User</button>
                <button class="tab" onclick="switchTab('new')">Invite New</button>
            </div>
            
            <div id="existingUserTab" class="tab-content active">
                <div class="form-group">
                    <label>Select User to Promote</label>
                    <select id="existingUserSelect" class="form-control">
                        <option value="">Choose a user...</option>
                        <?php foreach ($potentialModels as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars($user['username'] ?? $user['email']) ?> 
                                (<?= $user['role_name'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="promoteToModel()">Promote to Model</button>
            </div>
            
            <div id="newUserTab" class="tab-content">
                <form id="inviteModelForm" onsubmit="inviteModel(event)">
                    <div class="form-group">
                        <label for="inviteEmail">Email Address *</label>
                        <input type="email" id="inviteEmail" name="email" required class="form-control" placeholder="model@example.com">
                    </div>
                    <div class="form-group">
                        <label for="inviteName">Display Name</label>
                        <input type="text" id="inviteName" name="name" class="form-control" placeholder="Stage name or full name">
                    </div>
                    <div class="form-group">
                        <label for="inviteMessage">Personal Message</label>
                        <textarea id="inviteMessage" name="message" class="form-control" rows="3" placeholder="Optional message to include in invite email"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Invite</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--violet-500);
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.models-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.model-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.model-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.model-avatar {
    position: relative;
    flex-shrink: 0;
}

.model-avatar img,
.avatar-placeholder {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-placeholder {
    background: var(--violet-500);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
}

.verified-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    background: var(--success);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--bg-secondary);
}

.model-info {
    flex: 1;
    min-width: 0;
}

.model-info h3 {
    margin: 0 0 0.25rem;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.model-email {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.model-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.model-stats span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.model-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 0.5rem;
}

.tab {
    background: none;
    border: none;
    padding: 0.5rem 1rem;
    cursor: pointer;
    color: var(--text-muted);
    border-radius: 6px;
    transition: all 0.2s;
}

.tab.active,
.tab:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-state svg {
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}
</style>

<script>
function openAddModelModal() {
    document.getElementById('addModelModal').classList.add('active');
}

function closeAddModelModal() {
    document.getElementById('addModelModal').classList.remove('active');
}

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tab === 'existing' ? 'existingUserTab' : 'newUserTab').classList.add('active');
}

async function promoteToModel() {
    const userId = document.getElementById('existingUserSelect').value;
    if (!userId) {
        alert('Please select a user');
        return;
    }
    
    try {
        const response = await fetch('/api/models/promote', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        
        if (response.ok) {
            window.location.reload();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to promote user');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function inviteModel(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/models/invite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: formData.get('email'),
                name: formData.get('name'),
                message: formData.get('message')
            })
        });
        
        if (response.ok) {
            alert('Invitation sent successfully!');
            closeAddModelModal();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to send invitation');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function viewModelProfile(id) {
    window.location.href = `/models/${id}`;
}

function editModel(id) {
    window.location.href = `/dashboard/models/${id}/edit`;
}

async function removeModel(id) {
    if (!confirm('Remove this user from the models roster? They will be demoted to a regular user.')) return;
    
    try {
        const response = await fetch(`/api/models/${id}`, { method: 'DELETE' });
        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';
?>
