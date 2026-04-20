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
        private string $toName,
        private string $fromEmail,
        private string $fromName,
    ) {
    }

    public function send(ContactMessage $msg): void
    {
        $subject = sprintf('[Contact] %s — %s', $msg->subject, $msg->name);

        $text = $this->buildTextBody($msg);
        $html = nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($this->toEmail, $this->toName))
            ->replyTo(new Address($msg->email, $msg->name))
            ->subject($subject)
            ->text($text)
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendAutoReply(ContactMessage $msg): void
    {
        $html = $this->twig->render('email/contact_auto_reply.html.twig', [
            'name' => $msg->name,
            'message' => $msg->message,
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($msg->email, $msg->name))
            ->subject('We hebben je bericht ontvangen – Topbags')
            ->html($html);

        $this->mailer->send($email);
    }

    private function buildTextBody(ContactMessage $msg): string
    {
        return trim(sprintf(
            "Nieuwe contactaanvraag via Topbags\n\n" .
            "Naam: %s\n" .
            "E-mail: %s\n" .
            "Telefoon: %s\n" .
            "Onderwerp: %s\n" .
            "Bron: %s\n" .
            "IP: %s\n" .
            "User-Agent: %s\n\n" .
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
}