<?php

namespace App\Http\Middleware;

use App\Enums\DisputeStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Http\Resources\WalletResource;
use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentDetail;
use App\Models\User;
use App\Services\Money\Currency;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = "app";

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $rates = cache()->remember("currency-rates", 60, function () {
            return Currency::getAll()
                ->transform(function (Currency $currency) {
                    return [
                        "code" => $currency->getCode(),
                        "buy_price" => services()
                            ->market()
                            ->getBuyPrice($currency)
                            ->toPrecision(),
                        "sell_price" => services()
                            ->market()
                            ->getSellPrice($currency)
                            ->toPrecision(),
                    ];
                })
                ->sort(function ($currency) {
                    return in_array($currency["code"], ["rub", "usd", "eur"]);
                })
                ->reverse()
                ->values()
                ->toArray();
        });

        $orderQuery = Order::query()->where("status", OrderStatus::PENDING);
        $disputeQuery = Dispute::query()->where(
            "status",
            DisputeStatus::PENDING,
        );

        $userId = auth()->id();
        $userRole = isRouteFor("Merchant")
            ? "merchant"
            : (isRouteFor("Trader")
                ? "trader"
                : (isRouteFor("Super Admin")
                    ? "admin"
                    : "guest"));

        $pendingOrdersCount = cache()->remember(
            "pending_orders_{$userRole}_{$userId}",
            15,
            function () use ($orderQuery, $userRole, $userId) {
                if ($userRole === "merchant") {
                    return 0;
                } elseif ($userRole === "trader") {
                    return $orderQuery
                        ->clone()
                        ->whereRelation("paymentDetail", "user_id", $userId)
                        ->count();
                } elseif ($userRole === "admin") {
                    return $orderQuery->clone()->count();
                } else {
                    return 0;
                }
            },
        );

        $pendingDisputesCount = cache()->remember(
            "pending_disputes_{$userRole}_{$userId}",
            15,
            function () use ($disputeQuery, $userRole, $userId) {
                if ($userRole === "merchant") {
                    return 0;
                } elseif ($userRole === "trader") {
                    return $disputeQuery
                        ->clone()
                        ->whereRelation(
                            "order.paymentDetail",
                            "user_id",
                            $userId,
                        )
                        ->count();
                } elseif ($userRole === "admin") {
                    return $disputeQuery->clone()->count();
                } else {
                    return 0;
                }
            },
        );

        $onlineUsers = 0;
        $activeDetails = 0;
        $pendingWithdrawals = 0;

        if (auth()->check()) {
            $userId = auth()->id();

            if (isRouteFor("Super Admin")) {
                $onlineUsers = cache()->remember(
                    "online_users",
                    15,
                    function () {
                        return User::query()->where("is_online", true)->count();
                    },
                );

                $pendingWithdrawals = cache()->remember(
                    "pending_withdrawals",
                    15,
                    function () {
                        return Invoice::query()
                            ->where("status", InvoiceStatus::PENDING)
                            ->where("type", InvoiceType::WITHDRAWAL)
                            ->count();
                    },
                );
            } elseif (isRouteFor("Support")) {
                $onlineUsers = cache()->remember(
                    "online_users_support",
                    15,
                    function () {
                        return User::query()->where("is_online", true)->count();
                    },
                );

                $pendingOrdersCount = cache()->remember(
                    "pending_orders_support",
                    15,
                    function () use ($orderQuery) {
                        return $orderQuery->clone()->count();
                    },
                );

                $pendingDisputesCount = cache()->remember(
                    "pending_disputes_support",
                    15,
                    function () use ($disputeQuery) {
                        return $disputeQuery->clone()->count();
                    },
                );
            }

            if (isRouteFor("Trader")) {
                $activeDetails = cache()->remember(
                    "active_details_trader_{$userId}",
                    15,
                    function () use ($userId) {
                        return PaymentDetail::query()
                            ->whereNull("archived_at")
                            ->where("is_active", true)
                            ->where("user_id", $userId)
                            ->count();
                    },
                );
            } elseif (isRouteFor("Super Admin")) {
                $activeDetails = cache()->remember(
                    "active_details_admin",
                    15,
                    function () {
                        return PaymentDetail::query()
                            ->whereNull("archived_at")
                            ->where("is_active", true)
                            ->whereRelation("user", "is_online", true)
                            ->count();
                    },
                );
            }
        }

        $menu = [
            "pendingOrdersCount" => (int) $pendingOrdersCount,
            "pendingDisputesCount" => (int) $pendingDisputesCount,
            "onlineUsers" => (int) $onlineUsers,
            "activeDetails" => (int) $activeDetails,
            "pendingWithdrawals" => (int) $pendingWithdrawals,
        ];

        return [
            ...parent::share($request),
            "auth" => [
                "user" => $request->user(),
                "role" => $request->user()?->roles()?->first(),
                "is_admin" => $request->user()?->hasRole("Super Admin"),
                "is_support" => $request->user()?->hasRole("Support"),
                "is_impersonated" => $request->user()?->isImpersonated(),
                "can_see_finances" => (function () use ($request) {
                    if (!$request->user()) {
                        return false;
                    }
                    if (
                        !$request
                            ->user()
                            ->hasAnyRole(["Super Admin", "Support"])
                    ) {
                        return true;
                    }

                    $whitelistString = services()
                        ->settings()
                        ->getWhitelistedFinanceUserIds();
                    $whitelistString = str_replace(
                        ["\r", "\n", " "],
                        [",", ",", ""],
                        (string) $whitelistString,
                    );
                    $whitelistedIds = array_filter(
                        explode(",", $whitelistString),
                    );

                    return in_array(
                        (string) $request->user()->id,
                        $whitelistedIds,
                    );
                })(),
            ],
            "ziggy" => fn() => [
                // ...(new Ziggy)->toArray(),
                "location" => $request->url(),
            ],
            "flash" => [
                "message" => fn() => $request->session()->get("message"),
                "error" => fn() => $request->session()->get("error"),
            ],
            "data" => [
                "rates" => fn() => $rates,
                "wallet" => fn() => $request->user()
                    ? WalletResource::make($request->user()->wallet)->resolve()
                    : null,
                "hasPendingDisputes" => fn() => $request
                    ->user()
                    ?->hasRole("Trader")
                    ? $menu["pendingDisputesCount"] > 0
                    : 0,
            ],
            'menu' => $menu,
            'platformWallet' => fn () => services()->settings()->getPlatformWallet(),
            'supportLink' => fn () => services()->settings()->getSupportLink(),
        ];
    }
}
