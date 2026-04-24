<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdatePrimeTimeBonusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $primeTimeBonus = services()
            ->settings()
            ->getPrimeTimeBonus()
            ->toArray();
        $supportLink = services()->settings()->getSupportLink();
        $fundsOnHoldTime = services()->settings()->getFundsOnHoldTime();
        $maxPendingDisputes = services()->settings()->getMaxPendingDisputes();
        $maxRejectedDisputes = services()->settings()->getMaxRejectedDisputes();
        $depositLink = services()->settings()->getDepositLink();
        $platformWallet = services()->settings()->getPlatformWallet();
        $maxConsecutiveFailedOrders = services()
            ->settings()
            ->getMaxConsecutiveFailedOrders();
        $whitelistedFinanceUserIds = services()
            ->settings()
            ->getWhitelistedFinanceUserIds();

        return Inertia::render(
            "Settings/Index",
            compact(
                "primeTimeBonus",
                "supportLink",
                "fundsOnHoldTime",
                "maxPendingDisputes",
                "maxRejectedDisputes",
                "depositLink",
                "platformWallet",
                "maxConsecutiveFailedOrders",
                "whitelistedFinanceUserIds",
            ),
        );
    }

    public function updatePrimeTimeBonus(UpdatePrimeTimeBonusRequest $request)
    {
        services()
            ->settings()
            ->updatePrimeTimeBonus(
                starts: $request->starts,
                ends: $request->ends,
                rate: $request->rate,
            );

        return redirect()->route("admin.settings.index");
    }

    public function updateSupportLink(Request $request)
    {
        $request->validate(["support_link" => "required", "url:https"]);

        services()->settings()->updateSupportLink($request->support_link);

        return redirect()->route("admin.settings.index");
    }

    public function updateDepositLink(Request $request)
    {
        $request->validate(["deposit_link" => "required", "url:https"]);

        services()->settings()->updateDepositLink($request->deposit_link);

        return redirect()->route("admin.settings.index");
    }

    public function updateFundsOnHold(Request $request)
    {
        $request->validate(["hold_time" => "required", "integer", "min:0"]);

        services()->settings()->updateFundsOnHoldTime($request->hold_time);

        return redirect()->route("admin.settings.index");
    }

    public function updateMaxPendingDisputes(Request $request)
    {
        $request->validate([
            "max_pending_disputes" => "required",
            "integer",
            "min:0",
        ]);

        services()
            ->settings()
            ->updateMaxPendingDisputes($request->max_pending_disputes);

        return redirect()->route("admin.settings.index");
    }

    public function updateMaxRejectedDisputes(Request $request)
    {
        $request->validate([
            "count" => "required|integer|min:0",
            "period" => "required|integer|min:0",
        ]);

        services()
            ->settings()
            ->updateMaxRejectedDisputes(
                count: $request->count,
                period: $request->period,
            );

        return redirect()->route("admin.settings.index");
    }

    public function updatePlatformWallet(Request $request)
    {
        $request->validate(["platform_wallet" => "required|string"]);

        services()->settings()->updatePlatformWallet($request->platform_wallet);

        return redirect()->route("admin.settings.index");
    }

    public function updateMaxConsecutiveFailedOrders(Request $request)
    {
        $request->validate([
            "count" => "required|integer|min:0",
            "period" => "required|integer|min:0",
        ]);

        services()
            ->settings()
            ->updateMaxConsecutiveFailedOrders(
                count: $request->count,
                period: $request->period,
            );

        return redirect()->route("admin.settings.index");
    }

    public function getWhitelistedFinanceUserIds()
    {
        return services()->settings()->getWhitelistedFinanceUserIds();
    }

    public function updateWhitelistedFinanceUserIds(Request $request)
    {
        $request->validate([
            "whitelisted_finance_user_ids" => "nullable|string",
        ]);

        services()
            ->settings()
            ->updateWhitelistedFinanceUserIds(
                $request->whitelisted_finance_user_ids ?? "",
            );

        return redirect()->route("admin.settings.index");
    }
}
