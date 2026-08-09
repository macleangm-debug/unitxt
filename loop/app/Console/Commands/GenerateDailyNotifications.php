<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\DailyNotificationService;
use Illuminate\Console\Command;

class GenerateDailyNotifications extends Command
{
    protected $signature = 'loop:daily-notifications';

    protected $description = 'Generate bilingual-ready daily in-app notifications for business owners';

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

        $this->info("Created {$count} notifications.");

        return self::SUCCESS;
    }
}
