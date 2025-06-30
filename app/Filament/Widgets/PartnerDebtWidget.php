<?php

namespace App\Filament\Widgets;

use App\Models\Partnership;
use App\Models\Settlement;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PartnerDebtWidget extends Widget
{
    protected static string $view = 'filament.widgets.partner-debt';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Auth::user()->isPartner();
    }

    protected function getViewData(): array
    {
        $user = Auth::user();

        // Get all active partnerships for this partner
        $partnerships = Partnership::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->with(['store', 'settlements' => function ($query) {
                $query->latest()->take(5);
            }])
            ->get();

        // Calculate total debt across all partnerships
        $totalDebt = $partnerships->sum('debt_balance');

        // Get recent settlements
        $recentSettlements = Settlement::whereHas('partnership', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['partnership.store'])
            ->latest()
            ->take(10)
            ->get();

        // Get pending settlements count
        $pendingSettlements = Settlement::whereHas('partnership', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status', 'pending')
            ->count();

        return [
            'partnerships' => $partnerships,
            'totalDebt' => $totalDebt,
            'recentSettlements' => $recentSettlements,
            'pendingSettlements' => $pendingSettlements,
        ];
    }
}
