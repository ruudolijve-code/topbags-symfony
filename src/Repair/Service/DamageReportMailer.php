<?php

declare(strict_types=1);

namespace App\Repair\Service;

use App\Repair\Entity\DamageReport;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class DamageReportMailer
{
    private const BCC_ADDRESS =
        'maret.holtkamp@topbags.nl';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly DamageReportPdfGenerator $pdfGenerator,
    ) {
    }

    public function send(
        DamageReport $report
    ): void {
        $recipient = $report->getCustomerEmail();

        /*
         * Als het rapport geen e-mailadres heeft,
         * terugvallen op de gekoppelde order.
         */
        if (
            ($recipient === null || $recipient === '')
            && $report->getOrder() !== null
        ) {
            $recipient = $report
                ->getOrder()
                ->getCustomerEmail();
        }

        if (
            $recipient === null
            || trim($recipient) === ''
        ) {
            throw new \LogicException(
                'Dit schaderapport heeft geen e-mailadres van de klant.'
            );
        }

        $pdf = $this->pdfGenerator->generate(
            $report
        );

        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'maret.holtkamp@topbags.nl',
                    'Topbags Tassen & Koffers'
                )
            )
            ->to($recipient)
            ->bcc(self::BCC_ADDRESS)
            ->subject(
                sprintf(
                    'Schaderapport %s',
                    $report->getReportNumber()
                )
            )
            ->htmlTemplate(
                'repair/damage_report/email.html.twig'
            )
            ->context([
                'report' => $report,
            ])
            ->attach(
                $pdf,
                sprintf(
                    'schaderapport-%s.pdf',
                    $report->getReportNumber()
                ),
                'application/pdf'
            );

        $this->mailer->send($email);
    }
}