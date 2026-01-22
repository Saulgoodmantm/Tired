<div class="page-header">
    <div class="page-title">
        <a href="/sites" class="back-link">← Sites</a>
        <h1>Add Site</h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="/sites/add" method="POST" class="form">
            <div class="form-group">
                <label for="name">Site Name</label>
                <input type="text" id="name" name="name" required placeholder="My Website" class="form-input">
                <span class="form-hint">A friendly name for this site</span>
            </div>
            
            <div class="form-group">
                <label for="domain">Domain</label>
                <input type="text" id="domain" name="domain" required placeholder="example.com" class="form-input">
                <span class="form-hint">The domain name (without https://)</span>
            </div>
            
            <div class="form-group">
                <label for="repo">Repository URL</label>
                <input type="text" id="repo" name="repo" required placeholder="https://github.com/user/repo.git" class="form-input">
                <span class="form-hint">Git repository URL</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="branch">Branch</label>
                    <input type="text" id="branch" name="branch" value="main" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="php_version">PHP Version</label>
                    <select id="php_version" name="php_version" class="form-input">
                        <option value="8.2" selected>PHP 8.2</option>
                        <option value="8.1">PHP 8.1</option>
                        <option value="8.0">PHP 8.0</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="/sites" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Site</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>What happens next?</h2>
    </div>
    <div class="card-body">
        <ol class="setup-steps">
            <li>Site configuration will be saved</li>
            <li>When you click "Deploy", the repository will be cloned to <code>/home/deploy/TiredProductions/{domain}</code></li>
            <li>You can then set up nginx configuration and SSL</li>
            <li>Point your domain's DNS to this server</li>
        </ol>
    </div>
</div>
