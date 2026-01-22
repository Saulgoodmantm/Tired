<?php
/**
 * Dashboard - Messages / Contact Management
 */
$activePage = 'messages';
$pageTitle = 'Messages';

use App\Utils\Database;

// Get messages
$status = $_GET['status'] ?? '';
$where = $status ? "WHERE status = ?" : "";
$params = $status ? [$status] : [];

$messages = Database::query(
    "SELECT * FROM messages {$where} ORDER BY created_at DESC LIMIT 100",
    $params
);

// Stats
$unreadCount = Database::queryValue("SELECT COUNT(*) FROM messages WHERE status = 'unread'");
$totalCount = Database::queryValue("SELECT COUNT(*) FROM messages");
?>

<!-- Stats -->
<div class="stats-grid mb-xl">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(248, 113, 113, 0.1); color: var(--error);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Unread</span>
            <span class="stat-value"><?= $unreadCount ?? 0 ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(124, 107, 240, 0.1); color: var(--violet-400);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Messages</span>
            <span class="stat-value"><?= $totalCount ?? 0 ?></span>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card dashboard-card mb-lg">
    <div class="card-body">
        <div class="flex items-center gap-md">
            <a href="/dashboard/messages" class="btn <?= !$status ? 'btn-primary' : 'btn-ghost' ?>">All</a>
            <a href="/dashboard/messages?status=unread" class="btn <?= $status === 'unread' ? 'btn-primary' : 'btn-ghost' ?>">Unread</a>
            <a href="/dashboard/messages?status=read" class="btn <?= $status === 'read' ? 'btn-primary' : 'btn-ghost' ?>">Read</a>
            <a href="/dashboard/messages?status=replied" class="btn <?= $status === 'replied' ? 'btn-primary' : 'btn-ghost' ?>">Replied</a>
        </div>
    </div>
</div>

<!-- Messages List -->
<div class="messages-list">
    <?php if (empty($messages)): ?>
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <p>No messages yet</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $message): ?>
        <div class="message-card card <?= $message['status'] === 'unread' ? 'unread' : '' ?>" 
             data-id="<?= $message['id'] ?>">
            <div class="message-header">
                <div class="message-sender">
                    <div class="avatar-sm avatar-initials">
                        <?= strtoupper(substr($message['name'] ?? 'C', 0, 1)) ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($message['name']) ?></strong>
                        <span class="text-muted">&lt;<?= htmlspecialchars($message['email']) ?>&gt;</span>
                    </div>
                </div>
                <div class="message-meta">
                    <span class="message-date"><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></span>
                    <span class="badge badge-<?= match($message['status']) {
                        'unread' => 'error',
                        'read' => 'info',
                        'replied' => 'success',
                        default => 'warning'
                    } ?>"><?= ucfirst($message['status']) ?></span>
                </div>
            </div>
            
            <div class="message-subject">
                <?= htmlspecialchars($message['subject']) ?>
            </div>
            
            <div class="message-body">
                <?= nl2br(htmlspecialchars(substr($message['message'], 0, 300))) ?>
                <?php if (strlen($message['message']) > 300): ?>...<?php endif; ?>
            </div>
            
            <div class="message-actions">
                <button class="btn btn-ghost btn-sm" onclick="viewMessage(<?= $message['id'] ?>)">View</button>
                <a href="mailto:<?= $message['email'] ?>?subject=Re: <?= urlencode($message['subject']) ?>" 
                   class="btn btn-ghost btn-sm"
                   onclick="markReplied(<?= $message['id'] ?>)">Reply</a>
                <?php if ($message['status'] === 'unread'): ?>
                    <button class="btn btn-ghost btn-sm" onclick="markRead(<?= $message['id'] ?>)">Mark Read</button>
                <?php endif; ?>
                <button class="btn btn-ghost btn-sm text-error" onclick="deleteMessage(<?= $message['id'] ?>)">Delete</button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.messages-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.message-card {
    background: var(--bg-surface);
    border: 1px solid var(--bg-hover);
    border-radius: var(--radius-xl);
    padding: var(--space-lg);
    transition: all var(--transition-fast);
}

.message-card.unread {
    border-left: 3px solid var(--violet-500);
    background: rgba(124, 107, 240, 0.05);
}

.message-card:hover {
    border-color: var(--violet-500);
}

.message-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-md);
    flex-wrap: wrap;
    gap: var(--space-sm);
}

.message-sender {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.message-meta {
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.message-date {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.message-subject {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: var(--space-sm);
}

.message-body {
    color: var(--text-secondary);
    font-size: 0.9375rem;
    line-height: 1.6;
    margin-bottom: var(--space-md);
}

.message-actions {
    display: flex;
    gap: var(--space-sm);
    flex-wrap: wrap;
}

.avatar-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--bg-hover);
    color: var(--text-muted);
}

.text-error {
    color: var(--error);
}

.empty-state {
    text-align: center;
    padding: var(--space-xxl);
    color: var(--text-muted);
}

.empty-state svg {
    margin-bottom: var(--space-md);
    opacity: 0.5;
}
</style>

<script>
async function markRead(id) {
    await fetch(`/api/messages/${id}/read`, { method: 'POST' });
    document.querySelector(`.message-card[data-id="${id}"]`).classList.remove('unread');
}

async function markReplied(id) {
    await fetch(`/api/messages/${id}/replied`, { method: 'POST' });
}

function viewMessage(id) {
    markRead(id);
    window.location.href = `/dashboard/messages/${id}`;
}

async function deleteMessage(id) {
    if (!confirm('Delete this message?')) return;
    
    const response = await fetch(`/api/messages/${id}`, { method: 'DELETE' });
    if (response.ok) {
        document.querySelector(`.message-card[data-id="${id}"]`).remove();
    }
}
</script>
