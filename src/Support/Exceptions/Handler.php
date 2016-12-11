<?php
namespace FatPanda\Illuminate\Support\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException as CoreValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Illuminate\Container\Container;

/**
 * Renders Exceptions as JSON
 */
class Handler extends LumenHandler
{
    /**
     * A reference to the Plugin that utilizes this handler.
     */
    protected $plugin;

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

    public function __construct(Container $plugin)
    {
        $this->plugin = $plugin;
    }

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
        if ($this->shouldntReport($e)) {
            return;
        }

        try {
            $logger = $this->plugin->make('Psr\Log\LoggerInterface');
        } catch (Exception $ex) {
            throw $e; // throw the original exception
        }

        $logger->error($e);
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
        return ( defined('WP_DEBUG') && WP_DEBUG ) || $this->plugin->config('app.debug') || current_user_can('administrator');
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
        // TODO: use constant REST_REQUEST to detect a REST_REQUEST and respond accordingly
        $response = static::buildResponseData($e);
        return $this->plugin->response->json($response, $response['data']['status']);
    }
}
