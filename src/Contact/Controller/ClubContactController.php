<?php

namespace App\Contact\Controller;

use App\Contact\Form\ClubInterestType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

final class ClubContactController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.mailer_from_email%')]
        private string $fromEmail,
        #[Autowire('%app.mailer_from_name%')]
        private string $fromName,
        #[Autowire('%app.contact_to_email%')]
        private string $contactToEmail,
        #[Autowire('%app.contact_to_name%')]
        private string $contactToName,
    ) {
    }

    #[Route('/clubactie/interesse', name: 'club_action_interest', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(ClubInterestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($this->contactToEmail, $this->contactToName))
                ->replyTo(new Address($data['email'], $data['contactName']))
                ->subject('Nieuwe aanvraag clubactie: ' . $data['organizationName'])
                ->htmlTemplate('email/club_interest.html.twig')
                ->context([
                    'data' => $data,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Bedankt! We nemen zo snel mogelijk contact met je op.');

            return $this->redirectToRoute('club_action_interest');
        }

        return $this->render('contact/club_interest.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}