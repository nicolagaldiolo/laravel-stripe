<?php


namespace App;


use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Subscription
{
    protected $user;
    protected $stripe;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

    }

    public function createCustomer()
    {

        // Create a new customer object
        $customer = $this->stripe->customers->create([

            'email' => $this->user->email,
        ]);

        $this->user->update([
            'customer_id' => $customer->id
        ]);

        return $customer;
    }

    public function retrieve($id)
    {
        return $this->stripe->subscriptions->retrieve($id);
    }

    public function create($paymentMethodId, $priceId)
    {
        $customerId = $this->user->customer_id;

        $payment_method = $this->stripe->paymentMethods->retrieve($paymentMethodId);

        $payment_method->attach([
            'customer' => $customerId,
        ]);

        // Set the default payment method on the customer
        $this->stripe->customers->update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);

        // Create the subscription
        $subscription = $this->stripe->subscriptions->create([
            'customer' => $customerId,
            'items' => [
                [
                    'price' => $priceId,
                ],
            ],
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        $this->user->update([
            'subscription_id' => $subscription->id
        ]);

        return $subscription;
    }

    public function retryInvoice($paymentMethodId, $invoiceId)
    {
        $customerId = $this->user->customer_id;

        $payment_method = $this->stripe->paymentMethods->retrieve($paymentMethodId);

        $payment_method->attach([
            'customer' => $customerId,
        ]);

        // Set the default payment method on the customer
        $this->stripe->customers->update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);

        return $this->stripe->invoices->retrieve($invoiceId, [
            'expand' => ['payment_intent'],
        ]);
    }
}