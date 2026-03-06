<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeGroup;

class FeeGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FeeGroup::create(['name' => 'Core Fees']);
        FeeGroup::create(['name' => 'Service Fees']);
    }
}
