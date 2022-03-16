<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['code' => 405,
                    'type'    => 'failure',
                    'message' => $e->getMessage(),
                    'data' => []
                ], 405);
            }
        });
        
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['code' => 404,
                    'type'    => 'failure',
                    'message' => $e->getMessage() ? $e->getMessage() : 'Url not exists.',
                    'data' => []
                ], 404);
            }
        });
        
        $this->renderable(function (HttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['code' => $e->getStatusCode(),
                    'type'    => 'failure',
                    'message' => $e->getMessage(),
                    'data' => []
                ], $e->getStatusCode());
            }
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['code' => 401,
                    'type'    => 'failure',
                    'message' => 'Unauthenticated.',
                    'data' => []
                ], 401);
            }
        });
    }
}
