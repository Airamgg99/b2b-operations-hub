<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Seguridad: Solo los administradores pueden auditar
        if (!$user->hasAnyRole(['Super Admin', 'Company Admin'])) {
            abort(403, 'Unauthorized action.');
        }

        // 2. Iniciamos la consulta base
        $query = Activity::with(['causer']);

        // 3. Filtro Multi-tenant: Si NO es Super Admin, limitamos a su propia empresa
        if (!$user->hasRole('Super Admin')) {
            $companyId = $user->company_id;
            $query->whereHasMorph('causer', [User::class], function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // 4. Ejecutamos la consulta y paginamos
        $logs = $query->latest()
            ->paginate(15)
            ->through(fn ($log) => [
                'id' => $log->id,
                'description' => $log->description,
                'event' => $log->event,
                'causer_name' => $log->causer ? $log->causer->name : 'System / Auto',
                'subject_type' => class_basename($log->subject_type),
                'properties' => $log->properties,
                'created_at' => $log->created_at->format('M d, Y - H:i'),
            ]);

        // 5. Enviamos los datos a la vista
        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs
        ]);
    }
}
