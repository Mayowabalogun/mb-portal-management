<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/PropertyRepository.php';

/**
 * HomeService
 *
 * Responsibility:
 * - Compose landing-page data from repository calls.
 * - Hold formatting helpers used by views.
 */
class HomeService
{
    /** Data-access dependency for property and statistics queries. */
    private PropertyRepository $propertyRepo;

    public function __construct()
    {
        $this->propertyRepo = new PropertyRepository();
    }

    /**
     * Build all data required for the landing page in one payload.
     */
    public function getLandingPageData(): array
    {
        return [
            // Counter cards at the top of the page.
            'stats' => $this->propertyRepo->getPropertyStats(),
            // Tabbed property showcase data.
            'flats' => $this->propertyRepo->getVacantFlats(6),
            'hostels' => $this->propertyRepo->getVacantHostels(6),
            'shops' => $this->propertyRepo->getVacantShops(6),
            // SEO/browser title.
            'page_title' => 'HOME - MB REAL ESTATE AGENCY',
        ];
    }

    /**
     * Format numeric amount as Nigerian Naira currency.
     */
    public static function formatNaira(float $amount): string
    {
        return '₦' . number_format($amount, 2);
    }
}
