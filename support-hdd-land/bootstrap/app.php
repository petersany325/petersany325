<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // Portal remember cookie is encrypted by app code (EnsurePortalCustomer)
        $middleware->encryptCookies(except: [
            \App\Http\Middleware\EnsurePortalCustomer::REMEMBER_COOKIE,
        ]);

        // Customer installers activate/verify licenses without a browser CSRF session.
        $middleware->validateCsrfTokens(except: [
            'license/request-otp',
            'license/confirm-otp',
            'license/activate',
            'license/verify',
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\ForceCanonicalUrl::class,
            \App\Http\Middleware\EnsureLicensed::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('license/*'),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException
                && $previous->getModel() === \App\Models\Customer::class
            ) {
                if ($request->is('reports/customers*')) {
                    return redirect()
                        ->route('reports.customers')
                        ->with('error', 'این مشتری حذف شده یا یافت نشد.');
                }

                if ($request->is('customers*')) {
                    return redirect()
                        ->route('customers.index')
                        ->with('error', 'این مشتری حذف شده یا یافت نشد.');
                }
            }

            return null;
        });

        $exceptions->respond(function ($response, $exception, $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            $target = $request->is('cartable*') || $request->is('portal*')
                ? 'portal.login'
                : 'login';

            return redirect()
                ->route($target)
                ->with('error', 'نشست منقضی شد. صفحه تازه شد؛ دوباره وارد شوید.')
                ->withErrors([
                    'login' => 'نشست منقضی شد. صفحه را تازه کنید و دوباره وارد شوید.',
                    'phone' => 'نشست منقضی شد. دوباره کد پیامک بگیرید.',
                ]);
        });
    })->create();
