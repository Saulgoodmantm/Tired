<?php
/**
 * Sites Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once APP_PATH . '/Services/SiteManager.php';

class SitesController extends BaseController
{
    private SiteManager $siteManager;

    public function __construct()
    {
        parent::__construct();
        $this->siteManager = new SiteManager();
    }

    /**
     * List all sites
     */
    public function index(): void
    {
        $sites = $this->siteManager->getAll();
        
        // Get status for each site
        foreach ($sites as &$site) {
            $site['status'] = $this->siteManager->getStatus($site['id']);
        }

        $data = [
            'sites' => $sites,
            'flash' => $this->getFlash(),
        ];

        $this->render('sites', $data);
    }

    /**
     * Show add site form
     */
    public function showAdd(): void
    {
        $this->render('sites-add', ['flash' => $this->getFlash()]);
    }

    /**
     * Store new site
     */
    public function store(): void
    {
        $data = [
            'name' => $this->getPost('name'),
            'domain' => $this->getPost('domain'),
            'repo' => $this->getPost('repo'),
            'branch' => $this->getPost('branch', 'main'),
            'php_version' => $this->getPost('php_version', '8.2'),
        ];

        $result = $this->siteManager->add($data);

        if ($result['success']) {
            $this->flash('success', 'Site added successfully');
            $this->redirect('/sites');
        } else {
            $this->flash('error', $result['error']);
            $this->redirect('/sites/add');
        }
    }

    /**
     * Show edit site form
     */
    public function showEdit(): void
    {
        $id = $this->getQuery('id');
        $site = $this->siteManager->getById($id);

        if (!$site) {
            $this->flash('error', 'Site not found');
            $this->redirect('/sites');
            return;
        }

        $data = [
            'site' => $site,
            'status' => $this->siteManager->getStatus($id),
            'flash' => $this->getFlash(),
        ];

        $this->render('sites-edit', $data);
    }

    /**
     * Update site
     */
    public function update(): void
    {
        $id = $this->getPost('id');
        $data = [
            'name' => $this->getPost('name'),
            'repo' => $this->getPost('repo'),
            'branch' => $this->getPost('branch'),
            'php_version' => $this->getPost('php_version'),
        ];

        $result = $this->siteManager->update($id, $data);

        if ($result['success']) {
            $this->flash('success', 'Site updated successfully');
        } else {
            $this->flash('error', $result['error']);
        }

        $this->redirect('/sites/edit?id=' . $id);
    }

    /**
     * Deploy a site
     */
    public function deploy(): void
    {
        $id = $this->getPost('id');
        
        $result = $this->siteManager->deploy($id);

        if ($this->isAjax()) {
            $this->json($result);
        } else {
            if ($result['success']) {
                $this->flash('success', 'Site deployed successfully');
            } else {
                $this->flash('error', $result['error'] ?? 'Deploy failed');
            }
            $this->redirect('/sites');
        }
    }

    /**
     * Show site logs
     */
    public function logs(): void
    {
        $id = $this->getQuery('id');
        $type = $this->getQuery('type', 'access');
        $lines = (int) $this->getQuery('lines', 100);

        $site = $this->siteManager->getById($id);
        if (!$site) {
            $this->flash('error', 'Site not found');
            $this->redirect('/sites');
            return;
        }

        $result = $this->siteManager->getLogs($id, $type, $lines);

        $data = [
            'site' => $site,
            'logs' => $result['logs'] ?? '',
            'type' => $type,
            'lines' => $lines,
        ];

        $this->render('sites-logs', $data);
    }

    /**
     * API: Get log content
     */
    public function getLogContent(): void
    {
        $id = $this->getQuery('id');
        $type = $this->getQuery('type', 'access');
        $lines = (int) $this->getQuery('lines', 100);

        $result = $this->siteManager->getLogs($id, $type, $lines);
        $this->json($result);
    }

    /**
     * Delete a site
     */
    public function delete(): void
    {
        $id = $this->getPost('id');
        
        $result = $this->siteManager->delete($id);

        if ($result['success']) {
            $this->flash('success', 'Site removed from panel (files not deleted)');
        } else {
            $this->flash('error', $result['error']);
        }

        $this->redirect('/sites');
    }

    /**
     * Check if request is AJAX
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
