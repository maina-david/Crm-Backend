<?php

namespace Tests\Feature;

use App\Services\CustomerInformationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CustomerInformationTest extends TestCase
{
    // use DatabaseTransactions;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $customer_information_service = new CustomerInformationService();
        $customer_information = $customer_information_service->get_caller_information("251938048040", "8", "38");
        $this->assertEquals("+251938048040",$customer_information);
    }
}
