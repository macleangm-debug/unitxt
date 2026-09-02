<?php

namespace App\Services;

use App\Models\Business;

class SalesTipService
{
    /**
     * @return array{title_key: string, body_key: string, cta_key: string, url: string}
     */
    public function tipFor(Business $business): array
    {
        $sector = $business->sector ?: 'retail';
        $known = [
            'coffee', 'restaurants', 'fast_food', 'fashion', 'beauty', 'grocery',
            'electronics', 'fitness', 'hospitality', 'bars', 'bakery', 'health',
        ];
        $key = in_array($sector, $known, true) ? $sector : 'default';

        return [
            'title_key' => 'loop.sales_tip_'.$key.'_title',
            'body_key' => 'loop.sales_tip_'.$key.'_body',
            'cta_key' => 'loop.open_sale',
            'url' => route('till.index'),
        ];
    }
}
