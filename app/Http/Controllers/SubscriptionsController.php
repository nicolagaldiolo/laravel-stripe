<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionsController extends Controller
{

    public function __construct()
    {
        logger('Invocato server');
    }

    public function createCustomer()
    {
        try {
            $customer = Auth::user()->subscription()->createCustomer();

            return response(['customer' => $customer]);
        }catch (\Exception $e) {
            return response(['error' => [
                    'message' => $e->getMessage()
                ]
            ], 422);
        };
    }

    public function createSubscription()
    {
        $paymentMethodId = \request('paymentMethodId');
        $priceId = Plan::where('stripe_id', \request('priceId'))->firstOrFail()->stripe_id;;

        try {

            $subscription = Auth::user()->subscription()->create($paymentMethodId, $priceId);

            return response($subscription);

        } catch (\Exception $e) {

            return response(['error' => [
                    'message' => $e->getMessage()
                ]
            ], 422);
        }
    }

    public function retryInvoice()
    {
        try {

            $invoice = Auth::user()->subscription()->retryInvoice(
                \request('paymentMethodId'),
                \request('invoiceId')
            );

            return response($invoice);

        } catch (\Exception $e) {
            return response(['error' => [
                   'message' => $e->getMessage()
                ]
            ], 422);
        }

    }
}
