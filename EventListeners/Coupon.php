<?php

namespace AutomaticDiscount\EventListeners;

use AutomaticDiscount\Model\AutomaticDiscount;
use AutomaticDiscount\Model\AutomaticDiscountQuery;
use Exception;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Coupon\CouponCreateOrUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Coupon\CouponManager;
use Thelia\Coupon\FacadeInterface;
use Thelia\Form\CouponCreationForm;
use Thelia\Model\CouponQuery;
use Thelia\Model\Event\AddressEvent;

class Coupon implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack    $requestStack,
        private readonly CouponManager            $couponManager,
        private readonly FacadeInterface          $facade,
        private readonly EventDispatcherInterface $dispatcher
    )
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_coupon_creation' => ['addAutomaticField', 20],
            TheliaEvents::COUPON_UPDATE => ['manageAutomaticCoupon', 80],
            TheliaEvents::COUPON_CREATE => ['manageAutomaticCoupon', 80],
            TheliaEvents::CART_ADDITEM => ['updateOrderDiscount', 10],
            TheliaEvents::CART_UPDATEITEM => ['updateOrderDiscount', 10],
            TheliaEvents::CART_DELETEITEM => ['updateOrderDiscount', 10],
            TheliaEvents::CUSTOMER_LOGIN => ['updateOrderDiscount', 10],
            AddressEvent::POST_UPDATE => ['updateOrderDiscount', 10],
        ];
    }

    /**
     * @throws PropelException
     */
    public function updateOrderDiscount(): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        if (!$session instanceof Session || !$session->isStarted()) {
            return;
        }

        $this->addAutomaticsDiscount();

        $discount = $this->couponManager->getDiscount();

        $session
            ->getSessionCart($this->dispatcher)
            ->setDiscount($discount)
            ->save();

        $session
            ->getOrder()
            ->setDiscount($discount);
    }

    public function addAutomaticField(TheliaFormEvent $event): void
    {
        $isAutomaticDiscount = false;
        $formBuilder = $event->getForm()->getFormBuilder();

        if ($data = $formBuilder->getData()) {
            if (!empty($data['code'])) {
                $coupon = CouponQuery::create()
                    ->useAutomaticDiscountQuery()
                    ->endUse()
                    ->filterByCode($data['code'])
                    ->findOne();
                if ($coupon) {
                    $isAutomaticDiscount = true;
                }
            }
        }

        $formBuilder->add('automatic', CheckboxType::class, ["data" => $isAutomaticDiscount]);
    }

    /**
     * @throws Exception
     */
    public function manageAutomaticCoupon(CouponCreateOrUpdateEvent $event): void
    {
        $isAutomaticCoupon = $this->isAutomaticCoupon();
        $couponId = $event->getCouponModel()->getId();


        if (null !== $automatic = AutomaticDiscountQuery::create()->findPk($couponId)) {
            if (!$isAutomaticCoupon) {
                $automatic->delete();
            }
            return;
        }

        if (!$isAutomaticCoupon) {
            return;
        }

        (new AutomaticDiscount())
            ->setCouponId($couponId)
            ->save();
    }

    protected function isAutomaticCoupon(): bool
    {
        return $this->getParam('automatic');
    }

    protected function getParam($key): bool
    {
        if (null === $formData = $this->requestStack->getCurrentRequest()->get(CouponCreationForm::COUPON_CREATION_FORM_NAME)) {
            return false;
        }

        return isset($formData[$key]) && $formData[$key] === 'on';
    }

    /**
     * @throws PropelException
     */
    protected function addAutomaticsDiscount(): void
    {
        $automaticDiscountList = AutomaticDiscountQuery::create()->findAllEnabledAutomaticDiscount();
        foreach ($automaticDiscountList as $code) {
            $this->facade->pushCouponInSession($code);
        }
    }
}
