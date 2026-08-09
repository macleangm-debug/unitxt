<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('allow_pay_with_points')->default(false)->after('is_active');
            $table->unsignedInteger('pay_spend_step')->nullable()->after('allow_pay_with_points');
            $table->unsignedInteger('pay_points_per_step')->nullable()->after('pay_spend_step');
            $table->unsignedTinyInteger('pay_points_max_percent')->default(50)->after('pay_points_per_step');
        });

        Schema::table('redemptions', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('visit_id')->constrained()->nullOnDelete();
            $table->string('notes', 255)->nullable()->after('status');
        });

        // One wallet per customer per business (merge shop-scoped duplicates).
        $dupes = DB::table('memberships')
            ->select('business_id', 'customer_id', DB::raw('MIN(id) as keep_id'), DB::raw('SUM(points_balance) as total_balance'), DB::raw('SUM(lifetime_points) as total_lifetime'))
            ->groupBy('business_id', 'customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $row) {
            $ids = DB::table('memberships')
                ->where('business_id', $row->business_id)
                ->where('customer_id', $row->customer_id)
                ->pluck('id')
                ->all();
            $keep = (int) $row->keep_id;
            $move = array_values(array_filter($ids, fn ($id) => (int) $id !== $keep));

            if ($move === []) {
                continue;
            }

            DB::table('visits')->whereIn('membership_id', $move)->update(['membership_id' => $keep]);
            DB::table('point_transactions')->whereIn('membership_id', $move)->update(['membership_id' => $keep]);
            DB::table('redemptions')->whereIn('membership_id', $move)->update(['membership_id' => $keep]);
            DB::table('memberships')->where('id', $keep)->update([
                'points_balance' => (int) $row->total_balance,
                'lifetime_points' => (int) $row->total_lifetime,
            ]);
            DB::table('memberships')->whereIn('id', $move)->delete();
        }

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'customer_id']);
            $table->unique(['business_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'customer_id']);
            $table->unique(['shop_id', 'customer_id']);
        });

        Schema::table('redemptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shop_id');
            $table->dropColumn('notes');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'allow_pay_with_points',
                'pay_spend_step',
                'pay_points_per_step',
                'pay_points_max_percent',
            ]);
        });
    }
};
