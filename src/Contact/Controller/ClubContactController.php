<?php

namespace App\Contact\Controller;

use App\Contact\Form\ClubInterestType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

final class ClubContactController extends AbstractController
{
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
                ->from(new Address('no-reply@topbags.local', 'Topbags'))
                ->to(new Address('info@topbags.nl', 'Topbags'))
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