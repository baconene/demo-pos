<?php

namespace Tests\Feature;

use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoPosOrderFieldValidationTest extends TestCase
{
    /** @return array<string, array{array<string, mixed>, bool}> */
    public static function cases(): array
    {
        return [
            'dine-in table, no name succeeds' => [['order_type' => 'dine_in', 'table_number' => 'A1'], true],
            'dine-in missing table fails' => [['order_type' => 'dine_in', 'table_number' => ''], false],
            'takeout name succeeds' => [['order_type' => 'takeout', 'customer_name' => 'Alex'], true],
            'takeout missing name fails' => [['order_type' => 'takeout'], false],
            'delivery complete succeeds' => [['order_type' => 'delivery', 'customer_name' => 'Alex', 'customer_contact' => '09123456789', 'customer_address' => 'Demo Road'], true],
            'delivery missing name fails' => [['order_type' => 'delivery', 'customer_contact' => '09123456789', 'customer_address' => 'Demo Road'], false],
            'delivery missing contact fails' => [['order_type' => 'delivery', 'customer_name' => 'Alex', 'customer_address' => 'Demo Road'], false],
            'delivery missing address fails' => [['order_type' => 'delivery', 'customer_name' => 'Alex', 'customer_contact' => '09123456789'], false],
        ];
    }

    #[DataProvider('cases')]
    public function test_conditional_customer_requirements(array $fields, bool $valid): void
    {
        $rules = (new StoreOrderRequest())->rules();
        // Product existence is validated separately; this checks the per-order field matrix.
        $rules = array_intersect_key($rules, array_flip(['order_type', 'table_number', 'customer_name', 'customer_contact', 'customer_address']));
        $this->assertSame($valid, Validator::make($fields, $rules)->passes());
    }
}