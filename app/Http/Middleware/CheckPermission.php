<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Verifica se o usuário tem a permissão específica
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            Log::warning('CheckPermission: Usuário não autenticado');
            return redirect()->route('login');
        }

        $user = $request->user();

        Log::info('CheckPermission verificando', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'permission' => $permission,
        ]);

        $allowed = match($permission) {
            'manage_users' => $user->canManageUsers(),
            'delete_orders' => $user->canDeleteOrders(),
            'import_orders' => $user->canImportOrders(),
            'change_status' => $user->canChangeStatus(),
            'send_whatsapp' => $user->canSendWhatsApp(),
            'upload_images' => $user->canUploadImages(),
            'view_orders' => $user->canViewOrders(),
            default => false,
        };

        Log::info('CheckPermission resultado', [
            'permission' => $permission,
            'allowed' => $allowed,
        ]);

        if (!$allowed) {
            Log::warning('CheckPermission negado', [
                'user_email' => $user->email,
                'user_role' => $user->role,
                'permission' => $permission,
            ]);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para esta ação.'
                ], 403);
            }
            abort(403, 'Você não tem permissão para esta ação.');
        }

        return $next($request);
    }
}
