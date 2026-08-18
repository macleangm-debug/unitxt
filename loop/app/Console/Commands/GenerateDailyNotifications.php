<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\User;
use App\Services\DailyNotificationService;
use Illuminate\Console\Command;

class GenerateDailyNotifications extends Command
{
    protected $signature = 'loop:daily-notifications';

    protected $description = 'Generate daily in-app notifications for owners, members, and affiliates';

    public function handle(DailyNotificationService $daily): int
    {
        $count = 0;
        Business::query()
            ->where('is_active', true)
            ->whereNotNull('onboarding_completed_at')
            ->orderBy('id')
            ->chunkById(50, function ($businesses) use ($daily, &$count) {
                foreach ($businesses as $business) {
                    $count += $daily->generateForBusiness($business);
                }
            });

        User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($daily, &$count) {
                foreach ($users as $user) {
                    $count += $daily->generateForCustomer($user);
                }
            });

        User::query()
            ->where('role', User::ROLE_AFFILIATE)
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(50, function ($users) use ($daily, &$count) {
                foreach ($users as $user) {
                    $count += $daily->generateForAffiliate($user);
                }
            });

        $this->info("Created {$count} notifications.");

        return self::SUCCESS;
    }
}
