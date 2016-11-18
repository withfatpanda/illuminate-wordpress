<?php
namespace FatPanda\Illuminate\Support\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException as CoreValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;

/**
 * Renders Exceptions as JSON
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        CoreValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    static function buildResponseData(Exception $e)
    {
        $response = [ 
            'type' => get_class($e),
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'data' => [
                'status' => 500
            ]
        ];

        if ($e instanceof ModelNotFoundException) {
            $response['data']['status'] = 404;
        }

        if ($e instanceof HttpException) {
            $response['data']['status'] = $e->getStatusCode();
        }

        if ($e instanceof \FatPanda\Illuminate\Support\Exceptions\ValidationException) {
            $response['data']['errors'] = $e->messages();
        }

        if (static::isDebugMode()) {
            $response['line'] = $e->getLine();
            $response['file'] = $e->getFile();
            $response['trace'] = $e->getTraceAsString();
        }

        return $response;
    }

    static public function isDebugMode()
    {
        if (function_exists('config')) {
            return config('app.debug');
        } else if (current_user_can('administrator')) {
            return true;
        } else {
            return constant('WP_DEBUG') && WP_DEBUG;
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $response = static::buildResponseData($e);
        return response()->json($response, $response['data']['status']);
    }
}
