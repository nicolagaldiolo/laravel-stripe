<?php


namespace Tests\traits;


trait InteractsWithStripe
{

    protected function getPaymentMethod()
    {
        return $this->stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 10,
                'exp_year' => 2021,
                'cvc' => '314',
            ],
        ])->id;
    }

    protected function getPlan()
    {
        return 'price_1HeZzjC5g2b0G4Z4q3Fnvu4M';
    }
}