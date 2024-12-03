<?php

namespace Filament;

use Exception;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

if (! function_exists('Filament\authorize')) {
    /**
     * @param  Model|class-string<Model>  $model
     *
     * @throws AuthorizationException
     */
    function authorize(string $action, Model | string $model, bool $shouldCheckPolicyExistence = true): Response
    {
        $user = Filament::auth()->user();

        if (! $shouldCheckPolicyExistence) {
            return Gate::forUser($user)->authorize($action, $model);
        }

        $policy = Gate::getPolicyFor($model);

        if (filled($policy) && method_exists($policy, $action)) {
            return Gate::forUser($user)->authorize($action, $model);
        }

        if (Filament::isAuthorizationStrict()) {
            throw new Exception(blank($policy)
                ? "Strict authorization mode is enabled, but no policy was found for [{$model}]."
                : "Strict authorization mode is enabled, but no [{$action}()] method was found on [{$policy}].");
        }

        /** @var bool | Response | null $response */
        $response = invade(Gate::forUser($user))->callBeforeCallbacks( /** @phpstan-ignore-line */
            $user,
            $action,
            [$model],
        );

        if ($response === false) {
            throw new AuthorizationException;
        }

        if (! $response instanceof Response) {
            return Response::allow();
        }

        return $response->authorize();
    }
}
