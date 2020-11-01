<?php

namespace Tests\Feature;

use App\Http\Controllers\WebhooksController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebhooksControllerTest extends TestCase
{

    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testConvertsAStripeEventNameToMethodName()
    {

        $method = (new WebhooksController)->eventToMethod('customer.subscription.deleted');

        $this->assertEquals($method, 'handleCustomerSubscriptionDeleted');
    }

    public function testItDeactivatesAUsersSubscriptionIfDeletedOnStripesEnd()
    {

        $customer_id = 'fake_customer';

        $user = User::factory()->create([
            'customer_id' => $customer_id,
            'subscription_active' => true
        ]);

        $this->post('stripe/webhook', [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'customer' => $customer_id
                ]
            ]
        ]);

        $this->assertFalse($user->fresh()->isSubscribed());
    }
}
