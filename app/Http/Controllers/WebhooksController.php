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

    public function handleInvoicePaymentSucceeded($payload)
    {

        logger($payload);

        $this->retrieveUser($payload)->payments()->create([
            'amount' => $payload['data']['object']['total'],
            'invoice' => $payload['data']['object']['id'],
            'payment_intent' => $payload['data']['object']['payment_intent'],
        ]);
    }

    public function handleCustomerSubscriptionCreated($payload)
    {
        //dd($payload);
        //logger($payload);
    }

    public function handleCustomerSubscriptionDeleted($payload)
    {

        $this->retrieveUser($payload)->deactivate();
    }

    public function eventToMethod($event)
    {
        return 'handle' . Str::studly(str_replace('.','_',$event));
    }

    protected function retrieveUser($payload)
    {
        return User::byStripeId($payload['data']['object']['customer']);
    }
}
