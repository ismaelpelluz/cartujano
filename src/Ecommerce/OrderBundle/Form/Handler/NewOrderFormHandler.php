<?php

namespace Ecommerce\OrderBundle\Form\Handler;

use Ecommerce\OrderBundle\Entity\Order;
use Ecommerce\OrderBundle\Entity\OrderHistory;
use Ecommerce\OrderBundle\Entity\OrderHistoryLog;
use Ecommerce\OrderBundle\Entity\OrderItem;
use Ecommerce\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityManager;

class NewOrderFormHandler
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function handle(User $user, $cart, $dataBillingForm, Request $request)
    {
        if ($request->isMethod('POST')) {
            $payMethod = $request->request->get('payment_option');
            $address = $this->em->getRepository('LocationBundle:Address')->findOneById($request->request->get('delivery_address'));
            $shipmentOption = $this->em->getRepository('ItemBundle:Shipment')->findOneById($request->request->get('shipment_option'));
            $extraOption = $this->em->getRepository('ItemBundle:Extra')->findOneById($request->request->get('extra_option'));
            $comments = $request->request->get('user_comments');
            $billingRequired = $request->request->get('billing_required');

            if (!isset($payMethod) || !isset($address) || !isset($shipmentOption)) {
                return array('result' => false);
            }


            $order = new Order();
            $order->setAddress($address);
            $order->setShipment($shipmentOption);
            $shipmentOption->addOrder($order);
            $order->setExtra($extraOption);
            $order->setComment($comments);

            if ($billingRequired === 'required') {
                $dataBillingForm->handleRequest($request);
                $billingAddress = $this->em->getRepository('LocationBundle:Address')->findOneById($request->request->get('billing_address'));
                if (!isset($billingAddress)) {
                    return array('result' => false);
                }
                $dataBilling = $dataBillingForm->getData();
                $corporateName = $dataBilling->getCorporateName();
                if (!isset($corporateName)) {
                    $dataBilling->setCorporateName($dataBilling->getName());
                }

                $dataBilling->setAddress($billingAddress);

                $order->setDataBilling($dataBilling);
                $dataBilling->setOrder($order);
                $this->em->persist($dataBilling);
            }

            $cartItems = $cart->getCartItems();

            foreach ($cartItems as $cartItem) {
                $item = $this->em->getRepository('ItemBundle:Item')->findOneBy(array('id' => $cartItem->getId()));
                $orderItem = new OrderItem();
                $orderItem->setItem($item);
                $orderItem->setOrder($order);
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setPrice($cartItem->getSinglePrice());
                $order->addItem($orderItem);
                //$item->setStock($item->getStock() - $cartItem->getQuantity());
                $this->em->persist($orderItem);
                $this->em->persist($item);
            }

            $order->setCustomer($user);
            $order->setDate(new \DateTime('now'));
            $order->setStatus(Order::STATUS_IN_PROCESS);

            $orderHistory = new OrderHistory();
            $orderHistoryLog = new OrderHistoryLog();
            $orderHistoryLog->setLog(Order::STATUS_IN_PROCESS);
            $orderHistory->addLog($orderHistoryLog);
            $order->setOrderHistory($orderHistory);
            $orderHistoryLog->setOrderHistory($orderHistory);

            $this->em->persist($orderHistoryLog);
            $this->em->persist($orderHistory);
            $this->em->persist($order);
            $this->em->flush();
            return array('result' => true, 'order' => $order->getId(), 'payment' => $payMethod);
        }

        return array('result' => false);
    }

}