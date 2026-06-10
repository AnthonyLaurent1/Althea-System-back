<?php

namespace App\Controller\Admin;

use App\Dto\Admin\Dashboard\SalesPeriodFilterDto;
use App\Service\Admin\Dashboard\SalesAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use InvalidArgumentException;

#[Route('/api/admin/dashboard')]
#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly SalesAnalyticsService $salesAnalyticsService,
    ) {
    }

    #[Route('/sales/daily', name: 'api_admin_dashboard_sales_daily', methods: ['GET'])]
    public function daily(Request $request): JsonResponse
    {
        return $this->safeJson(fn () => $this->salesAnalyticsService->daily($this->createFilter($request)));
    }

    #[Route('/sales/weekly', name: 'api_admin_dashboard_sales_weekly', methods: ['GET'])]
    public function weekly(Request $request): JsonResponse
    {
        return $this->safeJson(fn () => $this->salesAnalyticsService->weekly($this->createFilter($request)));
    }

    #[Route('/sales/weekly-by-category', name: 'api_admin_dashboard_sales_weekly_by_category', methods: ['GET'])]
    public function weeklyByCategory(Request $request): JsonResponse
    {
        return $this->safeJson(fn () => $this->salesAnalyticsService->weeklyByCategory($this->createFilter($request)));
    }

    #[Route('/sales/category-share', name: 'api_admin_dashboard_sales_category_share', methods: ['GET'])]
    public function categoryShare(Request $request): JsonResponse
    {
        return $this->safeJson(fn () => $this->salesAnalyticsService->categoryShare($this->createFilter($request)));
    }

    private function createFilter(Request $request): SalesPeriodFilterDto
    {
        return new SalesPeriodFilterDto(
            $request->query->get('from'),
            $request->query->get('to'),
        );
    }

    private function safeJson(callable $callback): JsonResponse
    {
        try {
            return $this->json($callback());
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
