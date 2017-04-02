<?php

namespace FatPanda\Illuminate\WordPress\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule as BaseSchedule;
use Illuminate\Contracts\Cache\Repository as Cache;

class Schedule extends BaseSchedule
{
 
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    
}
