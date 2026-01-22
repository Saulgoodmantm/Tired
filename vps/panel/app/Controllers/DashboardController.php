<?php
/**
 * Dashboard Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once APP_PATH . '/Services/SystemStats.php';
require_once APP_PATH . '/Services/SiteManager.php';
require_once APP_PATH . '/Services/SSHService.php';

class DashboardController extends BaseController
{
    private SystemStats $stats;
    private SiteManager $siteManager;
    private SSHService $ssh;

    public function __construct()
    {
        parent::__construct();
        $this->stats = new SystemStats();
        $this->siteManager = new SiteManager();
        $this->ssh = new SSHService();
    }

    /**
     * Show dashboard
     */
    public function index(): void
    {
        $data = [
            'stats' => $this->stats->getAll(),
            'services' => $this->stats->getAllServicesStatus(),
            'sites' => $this->siteManager->getAll(),
            'flash' => $this->getFlash(),
        ];

        $this->render('dashboard', $data);
    }

    /**
     * API: Get system stats
     */
    public function getStats(): void
    {
        $this->json($this->stats->getAll());
    }

    /**
     * API: Get services status
     */
    public function getServices(): void
    {
        $this->json($this->stats->getAllServicesStatus());
    }

    /**
     * API: Restart a service
     */
    public function restartService(): void
    {
        $service = $this->getPost('service');
        
        $allowedServices = ['nginx', 'php8.2-fpm'];
        
        if (!in_array($service, $allowedServices)) {
            $this->json(['success' => false, 'error' => 'Service not allowed'], 400);
            return;
        }

        $result = $this->ssh->execute("sudo systemctl restart " . escapeshellarg($service));
        
        $this->json([
            'success' => $result['success'],
            'output' => $result['output'],
        ]);
    }
}
