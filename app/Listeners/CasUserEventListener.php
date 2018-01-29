<?php

namespace App\Listeners;
use App\Events\Model\CasUserTokenChangeEvent;

class CasUserEventListener
{

	/**
     * Handle cas users token refresh event.
     * @param App\Events\Model\CasUserTokenChangeEvent $event
     */
    public function onCasUserTokenChange(CasUserTokenChangeEvent $event) 
    {

    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
    	//listen CasUserTokenChangeEvent
    	$events->listen(
            'App\Events\Model\CasUserTokenChangeEvent',
            'App\Listeners\CasUserEventListener@onCasUserTokenChange'
        );
    }
}