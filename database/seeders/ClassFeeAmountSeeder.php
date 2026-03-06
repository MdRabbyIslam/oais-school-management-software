<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassFeeAmount;
use App\Models\Fee;

class ClassFeeAmountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Sample class fee assignments
        ClassFeeAmount::create(['fee_id' => 1, 'class_id' => 1, 'amount' => 1500.00]);
        ClassFeeAmount::create(['fee_id' => 1, 'class_id' => 2, 'amount' => 1600.00]);
        ClassFeeAmount::create(['fee_id' => 2, 'class_id' => 1, 'amount' => 500.00]);
        ClassFeeAmount::create(['fee_id' => 3, 'class_id' => 1, 'amount' => 100.00]);
    }
}
