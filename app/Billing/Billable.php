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

    public function isActive()
    {
        return $this->isSubscribed() || $this->isOnGracePeriod();
    }

    public function isOnGracePeriod()
    {
        if(!$endsAt = $this->subscription_end_at){
            return false;
        }

        return Carbon::now()->lt($endsAt);
    }

    public function deactivate($endDate = null)
    {

        $endDate = $endDate ?: Carbon::now();

        $this->update([
            'subscription_active' => false,
            'subscription_end_at' => $endDate
        ]);
    }

    public function activate()
    {
        $this->update([
            'subscription_active' => true,
            'subscription_end_at' => null
        ]);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}