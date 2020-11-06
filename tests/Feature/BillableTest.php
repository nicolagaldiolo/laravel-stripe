<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BillableTest extends TestCase
{

    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testItDeterminesIfAUsersSubscriptionIsActive()
    {
        $user = User::factory()->create([
            'subscription_id' => 'FAKE_STRIPE_ID',
            'subscription_active' => true,
            'subscription_end_at' => null,
        ]);

        $this->assertTrue($user->isActive());

        $user->update([
            'subscription_active' => false,
            'subscription_end_at' => Carbon::now()->addDays(2),
        ]);

        $this->assertTrue($user->isActive());

        $user->update([
            'subscription_active' => false,
            'subscription_end_at' => Carbon::now()->subDays(2),
        ]);

        $this->assertFalse($user->isActive());
    }

    public function testItDeterminesIfAUsersSubscriptionIsOnAGracePeriod()
    {
        $user = User::factory()->create([
            'subscription_end_at' => null,
        ]);

        $this->assertFalse($user->isOnGracePeriod());

        //
        $user->subscription_end_at = Carbon::now()->addDays(2);

        $this->assertTrue($user->isOnGracePeriod());

        //
        $user->subscription_end_at = Carbon::now()->subDays(2);

        $this->assertFalse($user->isOnGracePeriod());
    }
}
