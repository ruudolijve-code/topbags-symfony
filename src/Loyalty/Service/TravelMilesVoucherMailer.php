<?php

declare(strict_types=1);

namespace App\Loyalty\Service;

use App\Loyalty\Entity\TravelMilesMember;
use App\Loyalty\Entity\TravelMilesVoucher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class TravelMilesVoucherMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function sendGiftcard(
        TravelMilesVoucher $voucher,
        bool $allowResend = false,
    ): void {
        $member = $voucher->getMember();

        if (!$member instanceof TravelMilesMember) {
            throw new \RuntimeException('Deze voucher is niet gekoppeld aan een Travelmiles lid.');
        }

        if ($member->getEmail() === '') {
            throw new \RuntimeException('Dit Travelmiles lid heeft geen e-mailadres.');
        }

        if ($voucher->getStatus() === TravelMilesVoucher::STATUS_SENT && !$allowResend) {
            throw new \RuntimeException('Deze giftcard is al eerder verstuurd.');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('info@topbags.nl', 'Topbags Travelmiles'))
            ->replyTo(new Address('info@topbags.nl', 'Topbags'))
            ->to(new Address($member->getEmail(), $member->getFullName() ?: $member->getEmail()))
            ->subject('Je ' . $voucher->getFormattedAmount() . ' Travelmiles tegoed staat klaar')
            ->htmlTemplate('email/loyalty/travelmiles_giftcard.html.twig')
            ->context([
                'member' => $member,
                'voucher' => $voucher,
            ]);

        $this->mailer->send($email);

        $voucher->markAsSent();

        $member->setVoucherSent(true);

        if ($voucher->getCoupon() !== null) {
            $voucher->getCoupon()
                ->setIsActive(true)
                ->setEndsAt($voucher->getExpiresAt());
        }

        $this->entityManager->flush();
    }
}