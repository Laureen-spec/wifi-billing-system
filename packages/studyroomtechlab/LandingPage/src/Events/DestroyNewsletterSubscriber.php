<?php

namespace StudyRoomTechLab\LandingPage\Events;

use Illuminate\Foundation\Events\Dispatchable;
use StudyRoomTechLab\LandingPage\Models\NewsletterSubscriber;

class DestroyNewsletterSubscriber
{
    use Dispatchable;

    public function __construct(
        public NewsletterSubscriber $subscriber,
    ) {}
}