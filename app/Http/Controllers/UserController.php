<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        try {
            $search = $request->input('search');
            $companyId = $request->input('company_id');
            $trashed = $request->input('trashed'); // '' (activos) | 'only' (archivados)

            $allowedSorts = ['id', 'name', 'email'];
            $sort = in_array($request->input('sort'), $allowedSorts)
                ? $request->input('sort')
                : 'id';

            $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

            $users = User::query()
                ->with(['company' => fn ($query) => $query->withTrashed()])
                ->when($trashed === 'only', function ($query) {
                    $query->onlyTrashed();
                })
                ->when($companyId, function ($query, $companyId) {
                    $query->where('company_id', $companyId);
                })
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
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

            return Inertia::render('Users/Index', [
                'users' => $users,
                'companies' => $companies,
                'archivedCount' => User::onlyTrashed()->count(),
                'filters' => [
                    'search' => $search,
                    'company_id' => $companyId,
                    'sort' => $sort,
                    'direction' => $direction,
                    'trashed' => $trashed,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Error loading users directory: ' . $e->getMessage());

            return redirect()->route('dashboard')
                ->with('error', 'An unexpected error occurred while loading the users directory.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                'company_id' => ['nullable', 'exists:companies,id'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            User::create($validated);

            return redirect()->back()->with('success', 'User account created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error creating user: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Database error while creating the user account. Please try again.');
        }
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'company_id' => ['nullable', 'exists:companies,id'],
                'password' => ['nullable', 'confirmed', Password::defaults()],
            ]);

            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            $user->update($validated);

            return redirect()->back()->with('success', 'User profile updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("Error updating user ID {$user->id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to update the user record.');
        }
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        try {
            if ($request->user()->id === $user->id) {
                return redirect()->back()
                    ->with('error', 'Security restriction: You cannot archive your own active account.');
            }

            $user->delete();

            return redirect()->back()->with('success', 'User account archived successfully.');
        } catch (Throwable $e) {
            Log::error("Error archiving user ID {$user->id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to archive the user account at this time.');
        }
    }

    public function restore(int $id): RedirectResponse
    {
        try {
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();

            return redirect()->back()->with('success', "User '{$user->name}' restored successfully.");
        } catch (Throwable $e) {
            Log::error("Error restoring user ID {$id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to restore the archived user account.');
        }
    }
}
