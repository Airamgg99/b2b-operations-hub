<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CompanyController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        try {
            $search = $request->input('search');
            $trashed = $request->input('trashed'); // '' (activas) | 'only' (papelera)

            $allowedSorts = ['id', 'name', 'vat_number'];
            $sort = in_array($request->input('sort'), $allowedSorts)
                ? $request->input('sort')
                : 'id';

            $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

            $companies = Company::query()
                ->withCount('users')
                ->when($trashed === 'only', function ($query) {
                    $query->onlyTrashed();
                })
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('vat_number', 'like', "%{$search}%")
                          ->orWhere('id', $search);
                    });
                })
                ->orderBy($sort, $direction)
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Companies/Index', [
                'companies' => $companies,
                'archivedCount' => Company::onlyTrashed()->count(),
                'filters' => [
                    'search' => $search,
                    'sort' => $sort,
                    'direction' => $direction,
                    'trashed' => $trashed,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Error loading companies directory: ' . $e->getMessage());

            return redirect()->route('dashboard')
                ->with('error', 'An unexpected error occurred while loading the companies directory.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'vat_number' => ['required', 'string', 'max:50', 'unique:companies,vat_number'],
                'is_active' => ['required', 'boolean'],
            ]);

            Company::create($validated);

            return redirect()->back()->with('success', 'Company created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error creating company: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Database error while creating the company. Please try again.');
        }
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'vat_number' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('companies', 'vat_number')->ignore($company->id),
                ],
                'is_active' => ['required', 'boolean'],
            ]);

            $company->update($validated);

            return redirect()->back()->with('success', 'Company updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("Error updating company ID {$company->id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to update the company record.');
        }
    }

    public function destroy(Company $company): RedirectResponse
    {
        try {
            $company->delete();

            return redirect()->back()->with('success', 'Company archived successfully.');
        } catch (Throwable $e) {
            Log::error("Error archiving company ID {$company->id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to archive the company at this time.');
        }
    }

    public function restore(int $id): RedirectResponse
    {
        try {
            $company = Company::onlyTrashed()->findOrFail($id);
            $company->restore();

            return redirect()->back()->with('success', "Company '{$company->name}' restored successfully.");
        } catch (Throwable $e) {
            Log::error("Error restoring company ID {$id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to restore the archived company.');
        }
    }
}
