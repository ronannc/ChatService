<?php

namespace App\Providers;

use App\Exceptions\TokenClienteInvalidoException;
use App\Services\Auth\ClienteAutenticadoBroadcast;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\AtendenteContext;
use App\Support\SistemaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SistemaContext::class);
        $this->app->singleton(AtendenteContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Guard fino usado só pela autorização de canal privado de
        // broadcasting (config/auth.php `cliente-broadcast`, routes/
        // channels.php). Delega inteiramente a ValidarTokenClienteService —
        // não reimplementa nem afrouxa a validação do token do cliente.
        Auth::viaRequest('cliente-broadcast', function (Request $request) {
            try {
                $token = $this->app->make(ValidarTokenClienteService::class)
                    ->handle($request->bearerToken() ?? '');
            } catch (TokenClienteInvalidoException) {
                return null;
            }

            return new ClienteAutenticadoBroadcast($token);
        });
    }
}
