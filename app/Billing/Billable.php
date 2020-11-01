<?php


namespace App\Billing;


use App\Subscription;
use Carbon\Carbon;

trait Billable
{
    public static function byStripeId($stripeId)
    {
        return static::where('customer_id', $stripeId)->firstOrFail();
    }

    public function subscription()
    {
        return new Subscription($this);
    }

    public function isSubscribed()
    {
        return !! $this->subscription_active;
    }

    public function deactivate(){
        $this->update([
            'subscription_active' => false,
            'subscription_end_at' => Carbon::now()
        ]);
    }

    public function activate(){
        $this->update([
            'subscription_active' => true,
            'subscription_end_at' => null
        ]);
    }
}