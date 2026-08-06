<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('platform_settings')->where('key', 'billing')->exists();
        if (! $exists) {
            DB::table('platform_settings')->insert([
                'key' => 'billing',
                'value' => json_encode([
                    'trial_days' => 14,
                    'free_max_shops' => 1,
                    'free_max_members' => 50,
                    'free_max_monthly_visits' => 50,
                    'block_till_when_trial_ends' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('plans')->where('key', 'free')->update([
            'name' => 'Trial',
            'tagline' => 'Short trial — then pick a paid plan. People value what they pay for.',
            'max_shops' => 1,
            'max_members' => 50,
            'max_monthly_visits' => 50,
            'features' => json_encode([
                '1 physical shop + address',
                'Up to 50 members',
                'Up to 50 sales / month',
                '14-day trial then upgrade',
            ]),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('platform_settings')->where('key', 'billing')->delete();
    }
};
