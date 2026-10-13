<?php
namespace Database\Seeders;
use App\Models\PaymentTender;
use Illuminate\Database\Seeder;

class PaymentTenderSeeder extends Seeder {
    public function run(): void {
        $tenders = [
            ['name' => 'Cash',        'display_order' => 1],
            ['name' => 'GCash',       'display_order' => 2],
            ['name' => 'Maya',        'display_order' => 3],
            ['name' => 'Credit Card', 'display_order' => 4],
            ['name' => 'Debit Card',  'display_order' => 5],
        ];
        foreach ($tenders as $t) {
            PaymentTender::firstOrCreate(['name' => $t['name']], array_merge($t, ['is_active' => true]));
        }
    }
}
