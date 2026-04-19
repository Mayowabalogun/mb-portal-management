<?php
declare(strict_types=1);

require_once APP_ROOT . '/services/HomeService.php';

/**
 * HomeController
 *
 * Responsibility:
 * - Receive landing-page request.
 * - Ask HomeService for prepared page data.
 * - Pass normalized variables to the view.
 */
class HomeController
{
    /** Service layer dependency for landing-page orchestration. */
    private HomeService $homeService;

    public function __construct()
    {
        // Constructor keeps controller slim; business logic stays in service layer.
        $this->homeService = new HomeService();
    }

    /**
     * Render the landing page.
     */
    public function index(): void
    {
        // Single service call gives the complete payload needed by the view.
        $data = $this->homeService->getLandingPageData();

        // Explicit extraction keeps the template easy to read/maintain.
        $stats = $data['stats'];
        $availableFlats = $data['flats'];
        $availableHostels = $data['hostels'];
        $availableShops = $data['shops'];
        $page_title = $data['page_title'];

        // Render landing-page template.
        require APP_ROOT . '/views/home/index.php';
    }
}
