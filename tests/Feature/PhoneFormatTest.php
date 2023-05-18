<?php

namespace Tests\Feature;

use App\Services\PhoneFormatterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PhoneFormatTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $formatted_phone = PhoneFormatterService::format_phone("000254796671443");
        $this->assertEquals("254796671443", $formatted_phone);
        $formatted_phone = PhoneFormatterService::format_phone("+254796671443");
        $this->assertEquals("254796671443", $formatted_phone);
        $formatted_phone = PhoneFormatterService::format_phone("0796671443");
        $this->assertEquals("254796671443", $formatted_phone);
        $formatted_phone = PhoneFormatterService::format_phone("796671443");
        $this->assertEquals("254796671443", $formatted_phone);
    }
}
