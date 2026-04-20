<?php

namespace App\Contact\Service;

use App\Contact\Dto\ContactMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class ContactMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $toEmail,
        private string $toName = 'Topbags',
        private string $fromEmail = 'no-reply@topbags.local',
        private string $fromName = 'Topbags Contact',
    ) {}

    public function send(ContactMessage $msg): void
    {
        $subject = sprintf('[Contact] %s — %s', $msg->subject, $msg->name);

        $text = $this->buildTextBody($msg);
        $html = nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($this->toEmail, $this->toName))
            // reply-to is belangrijk: je wilt direct de klant kunnen beantwoorden
            ->replyTo(new Address($msg->email, $msg->name))
            ->subject($subject)
            ->text($text)
            ->html($html);

        $this->mailer->send($email);
    }

    private function buildTextBody(ContactMessage $msg): string
    {
        return trim(sprintf(
            "Nieuwe contactaanvraag via Topbags\n\n".
            "Naam: %s\n".
            "E-mail: %s\n".
            "Telefoon: %s\n".
            "Onderwerp: %s\n".
            "Bron: %s\n".
            "IP: %s\n".
            "User-Agent: %s\n\n".
            "Bericht:\n%s\n",
            $msg->name,
            $msg->email,
            $msg->phone ?: '-',
            $msg->subject,
            $msg->source ?: '-',
            $msg->ip ?: '-',
            $msg->userAgent ?: '-',
            $msg->message
        ));
    }

    //auto reply sturen naar klant als bevestiging dat bericht is ontvangen
    public function sendAutoReply(ContactMessage $msg): void
    {
        $html = $this->twig->render('email/contact_auto_reply.html.twig', [
            'name' => $msg->name,
            'message' => $msg->message,
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, 'Topbags'))
            ->to(new Address($msg->email, $msg->name))
            ->subject('We hebben je bericht ontvangen – Topbags')
            ->html($html);

        $this->mailer->send($email);
    }

    private function buildAutoReplyHtml(ContactMessage $msg): string
    {
        return '
        <div style="font-family: Arial, sans-serif; background:#f8f8f8; padding:40px 0;">
        <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.05);">

            <div style="background:#e30613;color:#ffffff;padding:24px 30px;">
            <h1 style="margin:0;font-size:22px;">Topbags</h1>
            <p style="margin:6px 0 0;font-size:14px;opacity:0.9;">Sinds 1900 – Specialist in koffers & reisbagage</p>
            </div>

            <div style="padding:30px;">

            <p style="font-size:16px;color:#111;">Beste '.$msg->name.',</p>

            <p style="font-size:14px;color:#444;line-height:1.6;">
                Bedankt voor je bericht. We hebben je vraag goed ontvangen en nemen deze zorgvuldig in behandeling.
                Je ontvangt meestal binnen <strong>1 werkdag</strong> een persoonlijk antwoord.
            </p>

            <div style="margin:25px 0;padding:18px;background:#f4f4f4;border-radius:12px;">
                <p style="margin:0;font-size:13px;color:#666;">
                <strong>Jouw bericht:</strong><br><br>
                '.nl2br(htmlspecialchars($msg->message)).'
                </p>
            </div>

            <h3 style="font-size:15px;margin-top:30px;color:#111;">Waarom kiezen voor Topbags?</h3>

            <ul style="font-size:13px;color:#555;line-height:1.8;padding-left:18px;">
                <li>✔ Meer dan 100 jaar ervaring</li>
                <li>✔ Officieel dealer van premium merken</li>
                <li>✔ Specialist in verhuur & reparatie</li>
                <li>✔ Persoonlijk advies op maat</li>
            </ul>

            <div style="margin-top:30px;text-align:center;">
                <a href="https://wa.me/31642485211"
                style="display:inline-block;background:#25D366;color:#ffffff;padding:12px 20px;border-radius:999px;text-decoration:none;font-size:13px;font-weight:bold;">
                💬 Direct WhatsApp contact
                </a>
            </div>

            <p style="margin-top:30px;font-size:13px;color:#666;line-height:1.6;">
                Heb je in de tussentijd nog vragen?  
                Bel ons gerust of kom langs in de winkel.
            </p>

            <p style="margin-top:25px;font-size:14px;color:#111;">
                Met vriendelijke groet,<br>
                <strong>Team Topbags</strong>
            </p>

            </div>

            <div style="background:#fafafa;padding:20px;text-align:center;font-size:12px;color:#888;">
            Topbags – Premium koffers & reisbagage<br>
            www.topbags.nl
            </div>

        </div>
        </div>
        ';
    }
}