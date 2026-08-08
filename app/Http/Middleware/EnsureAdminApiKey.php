<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-Admin-Api-Key');
        $configuredKey = config('chat.admin_api_key');

        if (! $configuredKey || ! hash_equals($configuredKey, (string) $providedKey)) {
            abort(401, 'Chave de API administrativa inválida ou ausente.');
        }

        return $next($request);
    }
}
