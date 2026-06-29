<?php

namespace App\Http\Middleware;

use App\Services\MenuVisibilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MenuRouteVisibilityMiddleware
{
    public function __construct(private readonly MenuVisibilityService $menuVisibility)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->menuVisibility->canAccessRoute($request, $request->user())) {
            abort(403, __('This menu is hidden for your role.'));
        }

        return $next($request);
    }
}
