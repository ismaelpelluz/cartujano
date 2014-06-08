<?php

namespace Ecommerce\FrontendBundle\EventListener;

use Ecommerce\FrontendBundle\Event\CartEvent;
use Ecommerce\FrontendBundle\Event\CartEvents;
use Ecommerce\FrontendBundle\Storage\CartStorageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Routing\Router;

class CartEventSubscriber implements EventSubscriberInterface
{
    protected $cartStorageManager;

    public static function getSubscribedEvents()
    {
        return array(
            CartEvents::NEW_CART => 'initializeCart'
        );
    }

    public function __construct(CartStorageManager $cartStorageManager)
    {
        $this->cartStorageManager = $cartStorageManager;
    }

    public function initializeCart(CartEvent $event)
    {
        if (!$this->cartStorageManager->getCurrentCart())
            $this->cartStorageManager->setCurrentCart($event->getCart());
    }
}