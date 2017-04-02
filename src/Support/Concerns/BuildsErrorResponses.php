<?php
namespace FatPanda\Illuminate\Support\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FatPanda\Illuminate\Support\Exceptions\ValidationException;

trait BuildsErrorResponses {

  /**
   * Given an Exception, build a data package suitable for reporting
   * the error to the client.
   * @param Exception The exception
   * @return array
   */
  public function buildErrorResponse(\Exception $e)
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

    if ($e instanceof ValidationException) {
        $response['data']['errors'] = $e->messages();
    }

    if ($this->isDebugMode()) {
        $response['line'] = $e->getLine();
        $response['file'] = $e->getFile();
        $response['trace'] = $e->getTraceAsString();
    }

    return $response;
  }

  public function isDebugMode()
  {
     return ( defined('WP_DEBUG') && WP_DEBUG ) || env('APP_DEBUG') || current_user_can('administrator');
  }


}