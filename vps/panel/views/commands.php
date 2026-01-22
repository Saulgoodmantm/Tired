<div class="page-header">
    <h1>Commands</h1>
</div>

<div class="commands-layout">
    <!-- Command Categories -->
    <div class="commands-sidebar">
        <div class="card">
            <div class="card-header">
                <h2>Categories</h2>
            </div>
            <div class="card-body">
                <div class="category-list">
                    <?php foreach ($commands['categories'] ?? [] as $catId => $category): ?>
                    <button class="category-btn" data-category="<?= htmlspecialchars($catId) ?>">
                        <span class="category-icon"><?= $this->getCategoryIcon($category['icon'] ?? 'terminal') ?></span>
                        <span><?= htmlspecialchars($category['name']) ?></span>
                    </button>
                    <?php endforeach; ?>
                    <?php if (!empty($commands['custom'])): ?>
                    <button class="category-btn" data-category="custom">
                        <span class="category-icon">⭐</span>
                        <span>Custom</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Site Selector -->
        <div class="card">
            <div class="card-header">
                <h2>Site Context</h2>
            </div>
            <div class="card-body">
                <select id="site-selector" class="form-input">
                    <option value="">No site selected</option>
                    <?php foreach ($sites ?? [] as $site): ?>
                    <option value="<?= htmlspecialchars($site['id']) ?>">
                        <?= htmlspecialchars($site['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Some commands use site path</span>
            </div>
        </div>
    </div>
    
    <!-- Main Commands Area -->
    <div class="commands-main">
        <!-- Command Grid -->
        <div class="card">
            <div class="card-header">
                <h2 id="category-title">Select a Category</h2>
            </div>
            <div class="card-body">
                <div class="commands-grid" id="commands-grid">
                    <p class="text-muted">Select a category to see available commands</p>
                </div>
            </div>
        </div>
        
        <!-- Custom Command -->
        <div class="card">
            <div class="card-header">
                <h2>Custom Command</h2>
            </div>
            <div class="card-body">
                <form id="custom-command-form" class="form">
                    <div class="form-row">
                        <div class="form-group flex-grow">
                            <input type="text" id="custom-command" name="custom_command" 
                                   placeholder="Enter a command to execute..." class="form-input">
                        </div>
                        <button type="submit" class="btn btn-primary">Execute</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Output -->
        <div class="card">
            <div class="card-header">
                <h2>Output</h2>
                <button class="btn btn-sm btn-ghost" onclick="clearOutput()">Clear</button>
            </div>
            <div class="card-body">
                <pre class="command-output" id="command-output">Ready to execute commands...</pre>
            </div>
        </div>
    </div>
    
    <!-- History Sidebar -->
    <div class="commands-history">
        <div class="card">
            <div class="card-header">
                <h2>History</h2>
            </div>
            <div class="card-body">
                <div class="history-list" id="history-list">
                    <?php foreach (array_reverse($history ?? []) as $entry): ?>
                    <div class="history-item">
                        <code class="small"><?= htmlspecialchars(substr($entry, 0, 50)) ?>...</code>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($history)): ?>
                    <p class="text-muted">No command history</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Save Custom Command -->
        <div class="card">
            <div class="card-header">
                <h2>Save Command</h2>
            </div>
            <div class="card-body">
                <form action="/commands/save" method="POST" class="form">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Command name" required class="form-input">
                    </div>
                    <div class="form-group">
                        <input type="text" name="command" placeholder="Command to run" required class="form-input">
                    </div>
                    <div class="form-group">
                        <input type="text" name="description" placeholder="Description (optional)" class="form-input">
                    </div>
                    <button type="submit" class="btn btn-secondary btn-block">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const commands = <?= json_encode($commands) ?>;

// Category icons
const icons = {
    'server': '🖥️',
    'cpu': '⚡',
    'file-text': '📄',
    'lock': '🔒',
    'git-branch': '🌿',
    'shield': '🛡️',
    'terminal': '⌨️'
};

// Category click handler
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const category = btn.dataset.category;
        showCategory(category);
        
        // Update active state
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    });
});

function showCategory(categoryId) {
    const grid = document.getElementById('commands-grid');
    const title = document.getElementById('category-title');
    
    let cmds;
    if (categoryId === 'custom') {
        cmds = commands.custom || [];
        title.textContent = 'Custom Commands';
    } else {
        const cat = commands.categories?.[categoryId];
        if (!cat) return;
        cmds = cat.commands || [];
        title.textContent = cat.name;
    }
    
    if (cmds.length === 0) {
        grid.innerHTML = '<p class="text-muted">No commands in this category</p>';
        return;
    }
    
    grid.innerHTML = cmds.map(cmd => `
        <div class="command-card" onclick="executeCommand('${cmd.id}')">
            <div class="command-name">${cmd.name}</div>
            <div class="command-desc">${cmd.description || ''}</div>
            <code class="command-code">${cmd.command}</code>
        </div>
    `).join('');
}

async function executeCommand(commandId) {
    const siteId = document.getElementById('site-selector').value;
    const output = document.getElementById('command-output');
    
    output.textContent = 'Executing...';
    
    try {
        const response = await fetch('/commands/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `command_id=${encodeURIComponent(commandId)}&site_id=${encodeURIComponent(siteId)}`
        });
        
        const data = await response.json();
        output.textContent = data.output || 'No output';
        output.className = 'command-output ' + (data.success ? 'output-success' : 'output-error');
    } catch (err) {
        output.textContent = 'Failed to execute command';
        output.className = 'command-output output-error';
    }
}

// Custom command form
document.getElementById('custom-command-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const command = document.getElementById('custom-command').value;
    const siteId = document.getElementById('site-selector').value;
    const output = document.getElementById('command-output');
    
    if (!command) return;
    
    output.textContent = 'Executing...';
    
    try {
        const response = await fetch('/commands/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `custom_command=${encodeURIComponent(command)}&site_id=${encodeURIComponent(siteId)}`
        });
        
        const data = await response.json();
        output.textContent = data.output || 'No output';
        output.className = 'command-output ' + (data.success ? 'output-success' : 'output-error');
    } catch (err) {
        output.textContent = 'Failed to execute command';
        output.className = 'command-output output-error';
    }
});

function clearOutput() {
    document.getElementById('command-output').textContent = 'Ready to execute commands...';
    document.getElementById('command-output').className = 'command-output';
}
</script>
