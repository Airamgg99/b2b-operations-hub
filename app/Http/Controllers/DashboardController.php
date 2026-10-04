<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        // Si el usuario no tiene permisos de administración, lo redirigimos a su directorio de equipo
        if (!$request->user()->hasAnyRole(['Super Admin', 'Company Admin'])) {
            return redirect()->route('users.index');
        }

        try {
            // KPIs de alto nivel
            $metrics = [
                'total_companies' => Company::count(),
                'active_companies' => Company::where('is_active', true)->count(),
                'total_users' => User::count(),
                'active_subscriptions' => Subscription::where('status', 'active')->count(),
            ];

            // Alertas: Suscripciones caducadas o que vencen en los próximos 30 días
            $alerts = Subscription::with(['company' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->where('status', 'past_due')
                ->orWhere(function ($query) {
                    $query->whereIn('status', ['active', 'trialing'])
                          ->whereNotNull('ends_at')
                          ->where('ends_at', '<=', now()->addDays(30));
                })
                ->orderBy('ends_at', 'asc')
                ->take(6)
                ->get();

            return Inertia::render('Dashboard', [
                'metrics' => $metrics,
                'alerts' => $alerts,
            ]);

        } catch (Throwable $e) {
            Log::error('Error loading dashboard metrics: ' . $e->getMessage());

            // En caso de fallo crítico en BD, carga la vista con datos vacíos para no romper la navegación
            return Inertia::render('Dashboard', [
                'metrics' => [
                    'total_companies' => 0,
                    'active_companies' => 0,
                    'total_users' => 0,
                    'active_subscriptions' => 0,
                ],
                'alerts' => [],
            ])->with('error', 'Unable to load dashboard metrics at this time.');
        }
    }
}
