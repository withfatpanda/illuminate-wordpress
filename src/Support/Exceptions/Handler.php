<?php
namespace FatPanda\Illuminate\Support\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException as CoreValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Illuminate\Container\Container;
use FatPanda\Illuminate\WordPress\Http\Router;
use Illuminate\Http\Response;
use FatPanda\Illuminate\Support\Concerns\BuildsErrorResponses;


/**
 * Renders Exceptions as JSON
 */
class Handler extends LumenHandler
{
    use BuildsErrorResponses;

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
            if ($this->plugin->bound('bugsnag.multi')) {
                $logger = $this->plugin->make('bugsnag.multi');
            } else {            
                $logger = $this->plugin->make('Psr\Log\LoggerInterface');
            }
            $logger->error($e);

        } catch (Exception $ex) {
            throw $e; // throw the original exception
        } finally {
            $this->renderException($e);
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
        return $this->renderException($e);
    }

    protected function renderException(Exception $e)
    {
        $error = $this->plugin->router->buildErrorResponse($e);
        
        if (defined('REST_REQUEST')) {
            status_header(500);
            header('Content-Type: application/json');
            echo json_encode($error);
            exit;

        } else {
            $message = $error['message'];
            if ($this->isDebugMode()) {
                $message .= " in {$error['file']}({$error['line']})";
            }

            wp_die($message);
        }
    }
}
