<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fee;
use App\Models\FeeGroup;

class FeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $coreFees = FeeGroup::where('name', 'Core Fees')->first();
        $serviceFees = FeeGroup::where('name', 'Service Fees')->first();

        Fee::create([
            'fee_group_id' => $coreFees->id,
            'fee_name' => 'Tuition Fee',
            'is_mandatory' => true,
            'recurring' => true,
            'payment_frequency' => 'Monthly',
            'is_service_fee' => false
        ]);

        Fee::create([
            'fee_group_id' => $coreFees->id,
            'fee_name' => 'Examination Fee',
            'is_mandatory' => true,
            'recurring' => false,
            'payment_frequency' => 'Annually',
            'is_service_fee' => false
        ]);

        Fee::create([
            'fee_group_id' => $serviceFees->id,
            'fee_name' => 'Library Fee',
            'is_mandatory' => false,
            'recurring' => true,
            'payment_frequency' => 'Quarterly',
            'is_service_fee' => true
        ]);

    }
}
