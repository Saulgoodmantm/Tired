<?php
/**
 * Dashboard - Locations Management
 * Manage shoot locations with photos and details
 */

$activePage = 'locations';
$pageTitle = 'Locations';

// Get locations from database
$db = \App\Utils\Database::getInstance();
$locations = $db->query("
    SELECT l.*, 
           COUNT(DISTINCT b.id) as booking_count,
           (SELECT li.image_url FROM location_images li WHERE li.location_id = l.id ORDER BY li.sort_order LIMIT 1) as cover_image
    FROM locations l
    LEFT JOIN bookings b ON b.location_id = l.id
    GROUP BY l.id
    ORDER BY l.name ASC
")->fetchAll();
?>

<?php ob_start(); ?>

<div class="dashboard-header">
    <div class="dashboard-header-content">
        <h1>Locations</h1>
        <p class="text-muted">Manage your shoot locations</p>
    </div>
    <button class="btn btn-primary" onclick="openLocationModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add Location
    </button>
</div>

<!-- Locations Grid -->
<div class="locations-grid">
    <?php if (empty($locations)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <h3>No locations yet</h3>
            <p>Add your first shoot location to get started</p>
            <button class="btn btn-primary" onclick="openLocationModal()">Add Location</button>
        </div>
    <?php else: ?>
        <?php foreach ($locations as $location): ?>
            <div class="location-card" data-id="<?= $location['id'] ?>">
                <div class="location-image">
                    <?php if ($location['cover_image']): ?>
                        <img src="<?= htmlspecialchars($location['cover_image']) ?>" alt="<?= htmlspecialchars($location['name']) ?>">
                    <?php else: ?>
                        <div class="location-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="location-overlay">
                        <button class="btn btn-sm btn-ghost" onclick="editLocation(<?= $location['id'] ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button class="btn btn-sm btn-ghost text-danger" onclick="deleteLocation(<?= $location['id'] ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="location-info">
                    <h3><?= htmlspecialchars($location['name']) ?></h3>
                    <p class="location-address">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?= htmlspecialchars($location['address'] ?? 'No address') ?>
                    </p>
                    <div class="location-meta">
                        <span class="badge badge-<?= $location['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $location['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                        <span class="text-muted"><?= $location['booking_count'] ?> bookings</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Location Modal -->
<div id="locationModal" class="modal">
    <div class="modal-backdrop" onclick="closeLocationModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="locationModalTitle">Add Location</h2>
            <button class="modal-close" onclick="closeLocationModal()">&times;</button>
        </div>
        <form id="locationForm" onsubmit="saveLocation(event)">
            <input type="hidden" name="id" id="locationId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="locationName">Location Name *</label>
                    <input type="text" id="locationName" name="name" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="locationAddress">Address</label>
                    <input type="text" id="locationAddress" name="address" class="form-control">
                </div>
                <div class="form-group">
                    <label for="locationDescription">Description</label>
                    <textarea id="locationDescription" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="locationLat">Latitude</label>
                        <input type="number" step="any" id="locationLat" name="latitude" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="locationLng">Longitude</label>
                        <input type="number" step="any" id="locationLng" name="longitude" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label for="locationNotes">Internal Notes</label>
                    <textarea id="locationNotes" name="notes" class="form-control" rows="2" placeholder="Parking info, access codes, etc."></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="locationActive" checked>
                        <span>Active (visible in booking)</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeLocationModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Location</button>
            </div>
        </form>
    </div>
</div>

<style>
.locations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.location-card {
    background: var(--bg-secondary);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: transform 0.2s, box-shadow 0.2s;
}

.location-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.location-image {
    position: relative;
    height: 180px;
    background: var(--bg-tertiary);
}

.location-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.location-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
}

.location-overlay {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.location-card:hover .location-overlay {
    opacity: 1;
}

.location-info {
    padding: 1rem;
}

.location-info h3 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
}

.location-address {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted);
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.location-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
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

.empty-state p {
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
</style>

<script>
function openLocationModal() {
    document.getElementById('locationModal').classList.add('active');
    document.getElementById('locationModalTitle').textContent = 'Add Location';
    document.getElementById('locationForm').reset();
    document.getElementById('locationId').value = '';
}

function closeLocationModal() {
    document.getElementById('locationModal').classList.remove('active');
}

async function editLocation(id) {
    try {
        const response = await fetch(`/api/locations/${id}`);
        const location = await response.json();
        
        document.getElementById('locationModalTitle').textContent = 'Edit Location';
        document.getElementById('locationId').value = location.id;
        document.getElementById('locationName').value = location.name;
        document.getElementById('locationAddress').value = location.address || '';
        document.getElementById('locationDescription').value = location.description || '';
        document.getElementById('locationLat').value = location.latitude || '';
        document.getElementById('locationLng').value = location.longitude || '';
        document.getElementById('locationNotes').value = location.notes || '';
        document.getElementById('locationActive').checked = location.is_active;
        
        document.getElementById('locationModal').classList.add('active');
    } catch (error) {
        console.error('Error loading location:', error);
    }
}

async function saveLocation(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    const data = {
        name: formData.get('name'),
        address: formData.get('address'),
        description: formData.get('description'),
        latitude: formData.get('latitude') || null,
        longitude: formData.get('longitude') || null,
        notes: formData.get('notes'),
        is_active: formData.has('is_active')
    };
    
    try {
        const url = id ? `/api/locations/${id}` : '/api/locations';
        const method = id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            window.location.reload();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to save location');
        }
    } catch (error) {
        console.error('Error saving location:', error);
    }
}

async function deleteLocation(id) {
    if (!confirm('Are you sure you want to delete this location?')) return;
    
    try {
        const response = await fetch(`/api/locations/${id}`, { method: 'DELETE' });
        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error deleting location:', error);
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';
?>
