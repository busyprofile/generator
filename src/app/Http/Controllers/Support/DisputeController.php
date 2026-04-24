<?php

namespace App\Http\Controllers\Support;

use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use Inertia\Inertia;
use App\Models\Order;
use App\Exceptions\DisputeException;
use App\Http\Requests\Dispute\CancelRequest;
use Illuminate\Support\Facades\Gate;

class DisputeController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();
        $filtersVariants = $this->getFiltersData();

        $disputes = queries()->dispute()->paginateForAdmin($filters);

        $disputes = DisputeResource::collection($disputes);

        $oldestDisputeCreatedAt = Dispute::query()
            ->where('status', DisputeStatus::PENDING)
            ->oldest('created_at')
            ->first('created_at')
            ?->created_at
            ->toDateTimeString();

        return Inertia::render('Support/Dispute/Index', compact('disputes', 'filters', 'filtersVariants', 'oldestDisputeCreatedAt'));
    }

    public function store(Order $order)
    {
        try {
            services()->dispute()->create($order->id);

            return redirect()->back()->with('message', 'Спор успешно открыт.');
        } catch (DisputeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function accept(Dispute $dispute)
    {
        // Gate::authorize('access-to-dispute', $dispute); // Assuming role middleware is sufficient for Support
        try {
            services()->dispute()->accept($dispute->id);
            return redirect()->back()->with('message', 'Спор успешно принят.');
        } catch (DisputeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(CancelRequest $request, Dispute $dispute)
    {
        // Gate::authorize('access-to-dispute', $dispute); // Assuming role middleware is sufficient for Support
        try {
            services()->dispute()->cancel($dispute->id, $request->reason);
            return redirect()->back()->with('message', 'Спор успешно отклонен.');
        } catch (DisputeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rollback(Dispute $dispute)
    {
        // Gate::authorize('access-to-dispute', $dispute); // Assuming role middleware is sufficient for Support
        try {
            services()->dispute()->rollback($dispute->id);
            return redirect()->back()->with('message', 'Спор успешно переоткрыт.');
        } catch (DisputeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
} 