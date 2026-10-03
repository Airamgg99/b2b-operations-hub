<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        try {
            $search = $request->input('search');
            $statusFilter = $request->input('status');
            $trashed = $request->input('trashed'); // '' (activas) | 'only' (archivadas)

            $allowedSorts = ['id', 'plan_name', 'status', 'starts_at', 'ends_at'];
            $sort = in_array($request->input('sort'), $allowedSorts)
                ? $request->input('sort')
                : 'id';

            $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

            $subscriptions = Subscription::query()
                ->with(['company' => fn ($query) => $query->withTrashed()])
                ->when($trashed === 'only', function ($query) {
                    $query->onlyTrashed();
                })
                ->when($statusFilter, function ($query, $statusFilter) {
                    $query->where('status', $statusFilter);
                })
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('plan_name', 'like', "%{$search}%")
                          ->orWhere('status', 'like', "%{$search}%")
                          ->orWhere('id', $search)
                          ->orWhereHas('company', function ($companyQuery) use ($search) {
                              $companyQuery->withTrashed()->where('name', 'like', "%{$search}%");
                          });
                    });
                })
                ->orderBy($sort, $direction)
                ->paginate(10)
                ->withQueryString();

            $companies = Company::query()
                ->select('id', 'name', 'is_active')
                ->orderBy('name')
                ->get();

            // Combina estados estándar con cualquier estado existente en la BD (seeders)
            $dbStatuses = Subscription::withTrashed()->select('status')->distinct()->pluck('status')->toArray();
            $availableStatuses = array_values(array_unique(array_merge(
                ['active', 'trialing', 'past_due', 'canceled', 'expired'],
                $dbStatuses
            )));

            // Combina planes estándar con cualquier plan existente en la BD (seeders)
            $dbPlans = Subscription::withTrashed()->select('plan_name')->distinct()->pluck('plan_name')->toArray();
            $availablePlans = array_values(array_unique(array_merge(
                ['Starter', 'Professional', 'Business', 'Enterprise'],
                $dbPlans
            )));

            return Inertia::render('Subscriptions/Index', [
                'subscriptions' => $subscriptions,
                'companies' => $companies,
                'availableStatuses' => $availableStatuses,
                'availablePlans' => $availablePlans,
                'archivedCount' => Subscription::onlyTrashed()->count(),
                'filters' => [
                    'search' => $search,
                    'status' => $statusFilter,
                    'sort' => $sort,
                    'direction' => $direction,
                    'trashed' => $trashed,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Error loading subscriptions directory: ' . $e->getMessage());

            return redirect()->route('dashboard')
                ->with('error', 'An unexpected error occurred while loading the subscriptions directory.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => ['required', 'exists:companies,id'],
                'plan_name' => ['required', 'string', 'max:100'],
                'status' => ['required', 'string', 'max:50'],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            ]);

            Subscription::create($validated);

            return redirect()->back()->with('success', 'Subscription created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error creating subscription: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Database error while creating the subscription. Please try again.');
        }
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => ['required', 'exists:companies,id'],
                'plan_name' => ['required', 'string', 'max:100'],
                'status' => ['required', 'string', 'max:50'],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            ]);

            $subscription->update($validated);

            return redirect()->back()->with('success', 'Subscription updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("Error updating subscription ID {$subscription->id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to update the subscription record.');
        }
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        try {
            $subscription->delete();

            return redirect()->back()->with('success', 'Subscription archived successfully.');
        } catch (Throwable $e) {
            Log::error("Error archiving subscription ID {$subscription->id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to archive the subscription at this time.');
        }
    }

    public function restore(int $id): RedirectResponse
    {
        try {
            $subscription = Subscription::onlyTrashed()->findOrFail($id);
            $subscription->restore();

            return redirect()->back()->with('success', "Subscription #{$subscription->id} restored successfully.");
        } catch (Throwable $e) {
            Log::error("Error restoring subscription ID {$id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to restore the archived subscription.');
        }
    }
}
