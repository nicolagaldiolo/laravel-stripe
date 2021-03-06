<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;

class CheckoutBaseCustomController extends Controller
{
    public function paymentIntent()
    {

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => 1400,
                'currency' => 'usd',
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (Error $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
