<?php
/**
 * Commands Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once APP_PATH . '/Services/SSHService.php';
require_once APP_PATH . '/Services/SiteManager.php';

class CommandsController extends BaseController
{
    private SSHService $ssh;
    private SiteManager $siteManager;

    public function __construct()
    {
        parent::__construct();
        $this->ssh = new SSHService();
        $this->siteManager = new SiteManager();
    }

    /**
     * Show commands page
     */
    public function index(): void
    {
        $data = [
            'commands' => $this->ssh->getAllCommands(),
            'sites' => $this->siteManager->getAll(),
            'history' => $this->ssh->getHistory(50),
            'flash' => $this->getFlash(),
        ];

        $this->render('commands', $data);
    }

    /**
     * Execute a command
     */
    public function execute(): void
    {
        $commandId = $this->getPost('command_id');
        $customCommand = $this->getPost('custom_command');
        $siteId = $this->getPost('site_id');

        $variables = [];
        
        // If site is selected, add site variables
        if ($siteId) {
            $site = $this->siteManager->getById($siteId);
            if ($site) {
                $variables['site_path'] = $site['path'];
                $variables['branch'] = $site['branch'];
                $variables['domain'] = $site['domain'];
            }
        }

        if ($commandId) {
            // Execute predefined command
            $result = $this->ssh->executeById($commandId, $variables);
        } elseif ($customCommand) {
            // Execute custom command
            $result = $this->ssh->execute($customCommand);
        } else {
            $result = ['success' => false, 'output' => 'No command provided'];
        }

        if ($this->isAjax()) {
            $this->json($result);
        } else {
            if ($result['success']) {
                $this->flash('success', 'Command executed successfully');
            } else {
                $this->flash('error', 'Command failed');
            }
            $this->redirect('/commands');
        }
    }

    /**
     * Save a custom command
     */
    public function save(): void
    {
        $command = [
            'name' => $this->getPost('name'),
            'command' => $this->getPost('command'),
            'description' => $this->getPost('description', ''),
            'dangerous' => (bool) $this->getPost('dangerous', false),
        ];

        if (empty($command['name']) || empty($command['command'])) {
            $this->flash('error', 'Name and command are required');
            $this->redirect('/commands');
            return;
        }

        $result = $this->ssh->saveCustomCommand($command);

        if ($result) {
            $this->flash('success', 'Command saved');
        } else {
            $this->flash('error', 'Failed to save command');
        }

        $this->redirect('/commands');
    }

    /**
     * Delete a custom command
     */
    public function delete(): void
    {
        $id = $this->getPost('id');
        
        $result = $this->ssh->deleteCustomCommand($id);

        if ($result) {
            $this->flash('success', 'Command deleted');
        } else {
            $this->flash('error', 'Failed to delete command');
        }

        $this->redirect('/commands');
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
