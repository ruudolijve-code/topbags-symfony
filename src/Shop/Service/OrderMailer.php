<?php

namespace App\Shop\Service;

use App\Shop\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail,
        private string $adminName,
        private string $fromEmail,
        private string $fromName,
    ) {
    }

    public function sendCustomerConfirmation(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($order->getCustomerEmail()))
            ->subject('Bevestiging van je bestelling ' . $order->getOrderNumber())
            ->htmlTemplate('email/order_customer_confirmation.html.twig')
            ->textTemplate('email/order_customer_confirmation.txt.twig')
            ->context([
                'order' => $order,
            ]);

        $this->mailer->send($email);
    }

    public function sendAdminNotification(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($this->adminEmail, $this->adminName))
            ->replyTo(new Address($order->getCustomerEmail()))
            ->subject('Nieuwe betaalde bestelling ' . $order->getOrderNumber())
            ->htmlTemplate('email/order_admin_notification.html.twig')
            ->textTemplate('email/order_admin_notification.txt.twig')
            ->context([
                'order' => $order,
            ]);

        $this->mailer->send($email);
    }

    public function sendShipmentNotification(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($order->getCustomerEmail()))
            ->subject('Je bestelling is verzonden: ' . $order->getOrderNumber())
            ->htmlTemplate('email/order_shipment_notification.html.twig')
            ->textTemplate('email/order_shipment_notification.txt.twig')
            ->context([
                'order' => $order,
            ]);

        $this->mailer->send($email);
    }
}