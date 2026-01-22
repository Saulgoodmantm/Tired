<?php
/**
 * Dashboard - Pricing Management
 * Manage service packages and add-ons
 */

$activePage = 'pricing';
$pageTitle = 'Pricing';

// Get pricing packages from database
$db = \App\Utils\Database::getInstance();
$packages = $db->query("
    SELECT * FROM pricing_packages 
    ORDER BY sort_order ASC, price ASC
")->fetchAll();

$addons = $db->query("
    SELECT * FROM pricing_addons 
    ORDER BY sort_order ASC, price ASC
")->fetchAll();

// Get service types with their base prices from settings or defaults
$serviceTypes = [
    'personal' => ['name' => 'Personal Session', 'price' => 150, 'duration' => 60],
    'portrait' => ['name' => 'Portrait Session', 'price' => 200, 'duration' => 90],
    'event' => ['name' => 'Event Coverage', 'price' => 300, 'duration' => 180],
    'wedding' => ['name' => 'Wedding Package', 'price' => 500, 'duration' => 480],
    'commercial' => ['name' => 'Commercial Shoot', 'price' => 400, 'duration' => 120],
    'fashion' => ['name' => 'Fashion Editorial', 'price' => 350, 'duration' => 120],
];
?>

<?php ob_start(); ?>

<div class="dashboard-header">
    <div class="dashboard-header-content">
        <h1>Pricing</h1>
        <p class="text-muted">Manage your service packages and add-ons</p>
    </div>
</div>

<!-- Service Types -->
<div class="pricing-section">
    <div class="section-header">
        <h2>Service Types</h2>
        <p class="text-muted">Base pricing for different session types</p>
    </div>
    
    <div class="pricing-grid">
        <?php foreach ($serviceTypes as $key => $service): ?>
            <div class="pricing-card service-card" data-type="<?= $key ?>">
                <div class="pricing-card-header">
                    <h3><?= htmlspecialchars($service['name']) ?></h3>
                    <div class="pricing-badge"><?= $service['duration'] ?> min</div>
                </div>
                <div class="pricing-price">
                    <span class="currency">$</span>
                    <span class="amount"><?= $service['price'] ?></span>
                </div>
                <button class="btn btn-ghost btn-sm" onclick="editServiceType('<?= $key ?>')">
                    Edit Pricing
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Packages -->
<div class="pricing-section">
    <div class="section-header">
        <h2>Packages</h2>
        <button class="btn btn-primary btn-sm" onclick="openPackageModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Package
        </button>
    </div>
    
    <div class="pricing-grid">
        <?php if (empty($packages)): ?>
            <div class="empty-card">
                <p>No packages defined yet</p>
                <button class="btn btn-primary btn-sm" onclick="openPackageModal()">Create First Package</button>
            </div>
        <?php else: ?>
            <?php foreach ($packages as $package): ?>
                <div class="pricing-card package-card" data-id="<?= $package['id'] ?>">
                    <?php if ($package['is_popular']): ?>
                        <div class="popular-badge">Popular</div>
                    <?php endif; ?>
                    <div class="pricing-card-header">
                        <h3><?= htmlspecialchars($package['name']) ?></h3>
                    </div>
                    <div class="pricing-price">
                        <span class="currency">$</span>
                        <span class="amount"><?= number_format($package['price'], 0) ?></span>
                    </div>
                    <p class="pricing-description"><?= htmlspecialchars($package['description'] ?? '') ?></p>
                    <?php if ($package['features']): ?>
                        <ul class="pricing-features">
                            <?php foreach (json_decode($package['features'], true) ?? [] as $feature): ?>
                                <li>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <?= htmlspecialchars($feature) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="pricing-card-actions">
                        <button class="btn btn-ghost btn-sm" onclick="editPackage(<?= $package['id'] ?>)">Edit</button>
                        <button class="btn btn-ghost btn-sm text-danger" onclick="deletePackage(<?= $package['id'] ?>)">Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add-ons -->
<div class="pricing-section">
    <div class="section-header">
        <h2>Add-ons</h2>
        <button class="btn btn-primary btn-sm" onclick="openAddonModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Add-on
        </button>
    </div>
    
    <div class="addons-list">
        <?php if (empty($addons)): ?>
            <div class="empty-card">
                <p>No add-ons defined yet</p>
                <button class="btn btn-primary btn-sm" onclick="openAddonModal()">Create First Add-on</button>
            </div>
        <?php else: ?>
            <?php foreach ($addons as $addon): ?>
                <div class="addon-row" data-id="<?= $addon['id'] ?>">
                    <div class="addon-info">
                        <h4><?= htmlspecialchars($addon['name']) ?></h4>
                        <p><?= htmlspecialchars($addon['description'] ?? '') ?></p>
                    </div>
                    <div class="addon-price">
                        +$<?= number_format($addon['price'], 0) ?>
                    </div>
                    <div class="addon-actions">
                        <button class="btn btn-ghost btn-sm" onclick="editAddon(<?= $addon['id'] ?>)">Edit</button>
                        <button class="btn btn-ghost btn-sm text-danger" onclick="deleteAddon(<?= $addon['id'] ?>)">Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Package Modal -->
<div id="packageModal" class="modal">
    <div class="modal-backdrop" onclick="closePackageModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="packageModalTitle">Add Package</h2>
            <button class="modal-close" onclick="closePackageModal()">&times;</button>
        </div>
        <form id="packageForm" onsubmit="savePackage(event)">
            <input type="hidden" name="id" id="packageId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="packageName">Package Name *</label>
                    <input type="text" id="packageName" name="name" required class="form-control" placeholder="e.g., Gold Package">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="packagePrice">Price ($) *</label>
                        <input type="number" id="packagePrice" name="price" required class="form-control" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="packageSort">Sort Order</label>
                        <input type="number" id="packageSort" name="sort_order" class="form-control" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="packageDescription">Description</label>
                    <textarea id="packageDescription" name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="packageFeatures">Features (one per line)</label>
                    <textarea id="packageFeatures" name="features" class="form-control" rows="4" placeholder="2 hour session&#10;50 edited photos&#10;Online gallery&#10;Print rights"></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_popular" id="packagePopular">
                        <span>Mark as Popular</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="packageActive" checked>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closePackageModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Package</button>
            </div>
        </form>
    </div>
</div>

<!-- Add-on Modal -->
<div id="addonModal" class="modal">
    <div class="modal-backdrop" onclick="closeAddonModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="addonModalTitle">Add Add-on</h2>
            <button class="modal-close" onclick="closeAddonModal()">&times;</button>
        </div>
        <form id="addonForm" onsubmit="saveAddon(event)">
            <input type="hidden" name="id" id="addonId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="addonName">Add-on Name *</label>
                    <input type="text" id="addonName" name="name" required class="form-control" placeholder="e.g., Rush Delivery">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="addonPrice">Price ($) *</label>
                        <input type="number" id="addonPrice" name="price" required class="form-control" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="addonSort">Sort Order</label>
                        <input type="number" id="addonSort" name="sort_order" class="form-control" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="addonDescription">Description</label>
                    <textarea id="addonDescription" name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="addonActive" checked>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeAddonModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Add-on</button>
            </div>
        </form>
    </div>
</div>

<style>
.pricing-section {
    margin-bottom: 3rem;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    margin: 0;
}

.section-header .text-muted {
    margin: 0;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.pricing-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.pricing-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.popular-badge {
    position: absolute;
    top: -10px;
    right: 1rem;
    background: var(--violet-500);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.pricing-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.pricing-card-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.pricing-badge {
    background: var(--bg-tertiary);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.pricing-price {
    margin-bottom: 1rem;
}

.pricing-price .currency {
    font-size: 1.5rem;
    color: var(--text-muted);
    vertical-align: top;
}

.pricing-price .amount {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--violet-500);
}

.pricing-description {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.pricing-features {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem;
}

.pricing-features li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--border-color);
}

.pricing-features li:last-child {
    border-bottom: none;
}

.pricing-features svg {
    color: var(--success);
    flex-shrink: 0;
}

.pricing-card-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.addons-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.addon-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem 1.5rem;
}

.addon-info {
    flex: 1;
}

.addon-info h4 {
    margin: 0 0 0.25rem;
    font-size: 1rem;
}

.addon-info p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.addon-price {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--violet-500);
}

.addon-actions {
    display: flex;
    gap: 0.5rem;
}

.empty-card {
    background: var(--bg-secondary);
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    color: var(--text-muted);
}

.empty-card p {
    margin-bottom: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
</style>

<script>
// Package Modal
function openPackageModal() {
    document.getElementById('packageModal').classList.add('active');
    document.getElementById('packageModalTitle').textContent = 'Add Package';
    document.getElementById('packageForm').reset();
    document.getElementById('packageId').value = '';
}

function closePackageModal() {
    document.getElementById('packageModal').classList.remove('active');
}

async function editPackage(id) {
    try {
        const response = await fetch(`/api/pricing/packages/${id}`);
        const pkg = await response.json();
        
        document.getElementById('packageModalTitle').textContent = 'Edit Package';
        document.getElementById('packageId').value = pkg.id;
        document.getElementById('packageName').value = pkg.name;
        document.getElementById('packagePrice').value = pkg.price;
        document.getElementById('packageSort').value = pkg.sort_order || 0;
        document.getElementById('packageDescription').value = pkg.description || '';
        document.getElementById('packageFeatures').value = (JSON.parse(pkg.features) || []).join('\n');
        document.getElementById('packagePopular').checked = pkg.is_popular;
        document.getElementById('packageActive').checked = pkg.is_active;
        
        document.getElementById('packageModal').classList.add('active');
    } catch (error) {
        console.error('Error loading package:', error);
    }
}

async function savePackage(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    const features = formData.get('features').split('\n').filter(f => f.trim());
    
    const data = {
        name: formData.get('name'),
        price: parseFloat(formData.get('price')),
        sort_order: parseInt(formData.get('sort_order')) || 0,
        description: formData.get('description'),
        features: JSON.stringify(features),
        is_popular: formData.has('is_popular'),
        is_active: formData.has('is_active')
    };
    
    try {
        const url = id ? `/api/pricing/packages/${id}` : '/api/pricing/packages';
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
            alert(error.message || 'Failed to save package');
        }
    } catch (error) {
        console.error('Error saving package:', error);
    }
}

async function deletePackage(id) {
    if (!confirm('Are you sure you want to delete this package?')) return;
    
    try {
        const response = await fetch(`/api/pricing/packages/${id}`, { method: 'DELETE' });
        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error deleting package:', error);
    }
}

// Add-on Modal
function openAddonModal() {
    document.getElementById('addonModal').classList.add('active');
    document.getElementById('addonModalTitle').textContent = 'Add Add-on';
    document.getElementById('addonForm').reset();
    document.getElementById('addonId').value = '';
}

function closeAddonModal() {
    document.getElementById('addonModal').classList.remove('active');
}

async function editAddon(id) {
    try {
        const response = await fetch(`/api/pricing/addons/${id}`);
        const addon = await response.json();
        
        document.getElementById('addonModalTitle').textContent = 'Edit Add-on';
        document.getElementById('addonId').value = addon.id;
        document.getElementById('addonName').value = addon.name;
        document.getElementById('addonPrice').value = addon.price;
        document.getElementById('addonSort').value = addon.sort_order || 0;
        document.getElementById('addonDescription').value = addon.description || '';
        document.getElementById('addonActive').checked = addon.is_active;
        
        document.getElementById('addonModal').classList.add('active');
    } catch (error) {
        console.error('Error loading add-on:', error);
    }
}

async function saveAddon(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    const data = {
        name: formData.get('name'),
        price: parseFloat(formData.get('price')),
        sort_order: parseInt(formData.get('sort_order')) || 0,
        description: formData.get('description'),
        is_active: formData.has('is_active')
    };
    
    try {
        const url = id ? `/api/pricing/addons/${id}` : '/api/pricing/addons';
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
            alert(error.message || 'Failed to save add-on');
        }
    } catch (error) {
        console.error('Error saving add-on:', error);
    }
}

async function deleteAddon(id) {
    if (!confirm('Are you sure you want to delete this add-on?')) return;
    
    try {
        const response = await fetch(`/api/pricing/addons/${id}`, { method: 'DELETE' });
        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error deleting add-on:', error);
    }
}

// Service Type Edit
function editServiceType(type) {
    // TODO: Implement service type pricing editor modal
    alert('Service type pricing editor coming soon. For now, edit in settings.');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';
?>
