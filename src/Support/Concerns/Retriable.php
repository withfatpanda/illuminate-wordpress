<?php
namespace FatPanda\Illuminate\Support\Concerns;

/**
 * Wrap any function call in a retry loop.
 *
 * Example Usage:
 * 
 *   Retriable::retry(function($attempt) {
 *
 *     // This is the operation we are trying
 *     // to complete, and retrying on failure.
 *     // wrap something like an HTTP request;
 *     // if it fails, throw an Exception; if
 *     // it succeeds, return the result—the 
 *     // result will be passed to the success
 *     // callback stack (@see then).
 *
 *   })->withDefault($defaultResult)
 *
 *     // In the event the wrapped operation fails,
 *     // a default result will be returned (@see until).
 *     // The $defaultResult given to this function
 *     // can be either a scalar value, or a function
 *     // that should be invoked, the return value of
 *     // which will be returned by until(). Using a
 *     // lambda function here would allow for expensive
 *     // default results to be deferred until after
 *     // the primary operation has failed.
 *
 *   })->unless(function(\Exception $e) {
 *
 *     // If $e is too severe for retries to continue,
 *     // then return false from an unless handler,
 *     // all subsequent attempts will be canceled
 *     // and the error stack will be processed.
 *     // To allow retries to continue your unless handler
 *     // must return true
 *     // If you install more than one unless handler,
 *     // the first one whose parameter type is an instance
 *     // of the exception type thrown will be called,
 *     // with all other handlers being skipped. Handlers
 *     // are invoked in the order they are configured,
 *     // so if you use more than one handler, install
 *     // the more specific ones first.
 *     // This handler is optional: the default
 *     // behavior is to keep retrying until
 *     // the limit is reached, then and only
 *     // then processing the appropriate outcome 
 *     // callback stack (@see then or @see fail).
 *
 *   })->withTimeout($timeout)
 *
 *   // $timeout is the amount of time to wait between
 *   // retry attempts, specified in seconds; the
 *   // default is 1 second
 *
 *   ->onWait($attempt, function(\Exception $e, $defaultTimeout) {
 *
 *     // In between attemps, the loop will wait
 *     // the prescribed amount of timeout time; before
 *     // that happens, this function will be called.
 *     // Use this function to change the amount of
 *     // time that should be waited after the given
 *     // Exception and/or on the given attempt—just
 *     // return an integer.
 *     // This handler is optional.
 *
 *   })->then(function($result, $attempt) {
 *
 *     // if no exceptions are thrown, then
 *     // stop retrying, and process the result.
 *     // Throw an Exception from this function
 *     // to kick the operation back into the
 *     // retry loop
 *
 *   })->fail(function(Exception $e, $attempt) {
 *   
 *     // If retry limit is reached without success
 *     // (or if an unless handler returns false, @see unless)
 *     // then this function is invoked and is given
 *     // the final Exception thrown by the operation.
 *     // If no fail handlers are added, any exception
 *     // thrown by the wrapped operation will be rethrown
 *     // by until()
 *
 *   })->until($limit);
 *
 *     // invoking until is what starts the loop;
 *     // $limit can be a function or it can be
 *     // an integer. If it is an integer, it represents
 *     // the maximum number of attempts that can be
 *     // made by the loop. If it is a function, then
 *     // after each unsuccessful attempt, that function
 *     // will be invoked, given the last Exception 
 *     // and the count representing the current attempt, e.g.,
 *     // function(\Exception $e, $attempt) {}. If
 *     // this function returns true, the retry loop will
 *     // continue; otherwise, it will be suspended.
 *     // Returned from this function will be either the
 *     // the result of the successfully executed wrapped
 *     // process, or the default (@see withDefault)
 *
 */
class Retriable {

  private $timeout = 1; // seconds
  private $attempts = 0;
  private $operation = null;
  private $limit = null;
  private $onError = [];
  private $onSuccess = [];
  private $onException = [];
  private $onWait = [];
  private $state = null;
  private $result = null;
  private $lastException = null;
  private $defaultResult = null;

  function __construct($operation) {
    $this->operation = $operation;
  }
  
  /**
   * Create a new RetryService for the given function
   * @param function
   * @return RetryService
   */
  static function retry($callback) {
    return new static($callback);
  }


  /**
   * If the wrapped operation ultimately fails on all attempts,
   * invoke the given function. Many functions can be
   * added to the stack. If the attempted execution
   * of the wrapped operation has already failed, the the function
   * given here will be invoked instantly.
   * @param function
   * @return self
   */
  function fail($callback) 
  { 
    if (is_null($this->state)) {
      $this->onError[] = $callback;
    } else if (!$this->state) {
      $callback($this->result);
    }
    return $this;
  }

  /**
   * If the wrapped operation ultimately succeeds,
   * invoke the given function. Many functions can be
   * added to the stack. If the attempted execution
   * of the wrapped operation has already succeeded, 
   * the the function given here will be invoked instantly.
   * If the given function throws an Exception, the
   * wrapped operation will be retried again—this allows
   * for instances in which the wrapped operation might
   * fail, but does not do so by throwing an Exception,
   * and success/failure is determined by post-processing.
   * @param function
   * @return self
   */
  function then($callback) 
  {
    if (is_null($this->state)) {
      $this->onSuccess[] = $callback;
    } else if ($this->state) {
      $callback($this->result);
    }
    return $this;
  }

  /**
   * Add functions to this stack to filter Exceptions
   * thrown by the wrapped operation. If any one of
   * the functions added here returns false, all 
   * subsequent attempts to execute the wrapped operation
   * will be suspended, and the error callback stack
   * will be processed.
   * @param function
   * @return self
   */
  function unless($callback) 
  {
    $this->onException[] = $callback;
    return $this;
  }

  /**
   * Add functions to this stack to be executed at the
   * beginning of the waiting periods between attempts
   * at processing the wrapped operation.
   * @param function
   * @return self
   */
  function onWait($callback)
  {
    $this->onWait[] = $callback;
    return $this;
  }

  function withTimeout($timeout)
  { 
    $this->timeout = (int) $timeout;
    return $this;
  }

  /**
   * Set the default result
   * @param mixed Either a scalar value or a function that
   * should be executed, of signature function(\Exception $e, $attempts)
   * @return self
   */
  function withDefault($defaultResult)
  {
    $this->defaultResult = $defaultResult;
    return $this;
  }

  /**
   * Start the loop with the given limit
   * @param mixed Can be an integer or a function of
   * the signature function(\Exception $e, $attempt),
   * where $e is the last Exception thrown and $attempt
   * is the count of attempts made to execute the wrapped
   * operation
   * @return The return result of the wrapped operation,
   * if and when it completes successfully.
   * @throws Exception If there are no functions in the
   * fail stack, the final exception thrown by the
   * wrapped operation will be rethrown by this function
   */
  function until($limit) 
  {
    $until = function() use ($limit) {
      if (is_int($limit)) {
        if ($this->attempts < $limit) {
          return true;
        } else {
          return false;
        }

      } else {
        if ($limit($this->lastException, $this->attempts)) {
          return true;
        } else {
          return false;
        }

      }
    };  

    $defaultResult = function() {
      if (is_callable($this->defaultResult)) {
        return call_user_func_array($this->defaultResult, [ $this->lastException, $this->attempts ]);
      } else {
        return $this->defaultResult;
      }
    };

    do {
      $this->attempts++;

      if ($this->attempts > 1) {
        if ($timeout = $this->handleWait($this->lastException)) {
          // DEBUG: echo "sleep($timeout)\n";
          sleep($timeout);
        }
      }

      try {
        // try to execute the wrapped process
        $result = call_user_func_array($this->operation, [ $this->attempts ]);
        // this might throw an Exception too:
        $this->handleSuccess($result);
        // it didn't throw an exception, so we're done!
        return $result;

      } catch (\Exception $e) {
        $this->lastException = $e;
        // let's filter the exception:
        if (!$this->handleException($e)) {
          // filtering the exception resulted in false, so we're done:
          $this->handleError($e);
          // just return the default result
          return $defaultResult();
        }

      }

    } while ($until());

    // we fell through to here, so we invoke handleError
    // DEBUG: echo "Fell through at: ".time()."\n";
    $this->handleError($this->lastException, $this->attempts);
    // and we just return whatever the default value of result was
    return $defaultResult();
  }

  private function handleWait(\Exception $e)
  { 
    $timeout = $this->timeout;
    if ($this->onWait) {
      foreach($this->onWait as $callback) {
        $timeout = $callback($this->attempts, $e, $this->timeout);
      }
    }
    return (int) $timeout;
  }

  private function handleException(\Exception $e) 
  {
    $result = true;
    if ($this->onException) {
      foreach($this->onException as $callback) {
        $reflect = new \ReflectionFunction($callback);
        $params = $reflect->getParameters();
        if (empty($params)) {
          throw new \Exception("Unless handler does not have enough parameters: must have at least 1, inheriting from Exception");
        }
        if ($params[0]->getClass()->isInstance($e)) {
          $result = $callback($e);  
          break;
        }
      }
    }
    return $result;
  }

  private function handleSuccess($result)
  {
    if ($this->onSuccess) {
      foreach($this->onSuccess as $callback) {
        // if this throws an Exception, we'll wind
        // up catching it in RetryService::until
        $callback($result, $this->attempts);
      }
    }
    $this->result = $result;
    $this->state = true;
  }

  private function handleError(\Exception $exception)
  {
    $this->state = false;
    if ($this->onError) {
      foreach($this->onError as $callback) {
        $callback($exception, $this->attempts);
      }
    } else {
      throw $exception;
    }
  }
  
}