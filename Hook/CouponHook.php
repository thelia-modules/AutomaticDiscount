<?php

namespace AutomaticDiscount\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class CouponHook extends BaseHook
{
    public function onCouponJs(HookRenderEvent $event): void
    {
        $event->add($this->render('automatic-input.html'));
    }

    public static function getSubscribedHooks(): array
    {
        return [
            "coupon.update-js" => [
                [
                    "type" => "back",
                    "method" => "onCouponJs"
                ]
            ]
        ];
    }
}
