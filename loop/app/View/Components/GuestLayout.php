<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public function __construct(
        public ?string $asideTitle = null,
        public ?string $asideBody = null,
        public ?string $asideStamp = null,
        public ?string $asidePoint1 = null,
        public ?string $asidePoint2 = null,
        public ?string $asidePoint3 = null,
    ) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
