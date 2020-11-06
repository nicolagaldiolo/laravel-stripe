<?php


namespace App;


use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Hamcrest\Thingy;
use Illuminate\Support\Facades\Auth;

class Subscription
{
    protected $user;
    protected $stripe;
    protected $coupon;

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

    public function deleteCustomer()
    {
        $this->stripe->customers->delete($this->user->customer_id);
    }

    public function retrieve()
    {
        return $this->stripe->subscriptions->retrieve($this->user->subscription_id);
    }

    public function usingCoupon($coupon = false)
    {
        if($coupon){
            $this->coupon = $coupon;
        }

        return $this;
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
            'coupon' => $this->coupon,
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        $this->user->update([
            'subscription_id' => $subscription->id
        ]);

        if($subscription->status === 'active'){
            $this->user->activate();
        }

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

    public function cancel($atPeriodEnd = true)
    {
        if($atPeriodEnd){
            $subscription = $this->stripe->subscriptions->update($this->user->subscription_id, [
                'cancel_at_period_end' => true,
            ]);
        }else{
            $subscription = $this->stripe->subscriptions->retrieve($this->user->subscription_id)->delete();
        }

        $endDate = Carbon::createFromTimestamp($subscription->current_period_end);
        $this->user->deactivate($endDate);

        return $subscription;

    }

    public function cancelImmediatly()
    {
        return $this->cancel(false);
    }

    public function resume()
    {
        $this->stripe->subscriptions->update($this->user->subscription_id, [
            'cancel_at_period_end' => false,
        ]);

        $this->user->activate();
    }
}