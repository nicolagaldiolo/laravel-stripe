<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhooksController extends Controller
{
    public function  handle()
    {
        $payload = \request()->all();

        if(method_exists($this, $method = $this->eventToMethod($payload['type']))){
            $this->$method($payload);
        }

        return response('Webhook received');
    }

    public function handleCustomerSubscriptionDeleted($payload)
    {

        User::byStripeId(
            $payload['data']['object']['customer']
        )->deactivate();
    }

    public function eventToMethod($event)
    {
        return 'handle' . Str::studly(str_replace('.','_',$event));
    }
}
