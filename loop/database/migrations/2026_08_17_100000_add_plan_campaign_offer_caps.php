<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_product_pushes')->nullable()->after('max_monthly_visits');
            $table->unsignedSmallInteger('max_offers')->nullable()->after('max_product_pushes');
        });

        $caps = [
            'free' => ['max_product_pushes' => 1, 'max_offers' => 3],
            'starter' => ['max_product_pushes' => 3, 'max_offers' => 8],
            'growth' => ['max_product_pushes' => 10, 'max_offers' => 20],
            'scale' => ['max_product_pushes' => null, 'max_offers' => null],
        ];

        foreach ($caps as $key => $values) {
            DB::table('plans')->where('key', $key)->update([
                ...$values,
                'updated_at' => now(),
            ]);
        }

        $setting = DB::table('platform_settings')->where('key', 'billing')->first();
        if ($setting) {
            $value = json_decode((string) $setting->value, true);
            if (! is_array($value)) {
                $value = [];
            }
            $value['free_max_product_pushes'] = $value['free_max_product_pushes'] ?? 1;
            $value['free_max_offers'] = $value['free_max_offers'] ?? 3;
            DB::table('platform_settings')->where('key', 'billing')->update([
                'value' => json_encode($value),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_product_pushes', 'max_offers']);
        });
    }
};
