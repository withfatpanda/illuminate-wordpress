<?php
namespace FatPanda\Illuminate\Support\Concerns;

trait CanRetryThings {

  function retry($callback) {
    return new Retriable($callback);
  }
  
}