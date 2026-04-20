<?php

namespace App\Contact\Controller;

use App\Contact\Entity\ContactMessage as ContactEntity;
use App\Contact\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'limiter.contact_form')]
        RateLimiterFactory $contactFormLimiter
    ): Response {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('website') && $form->get('website')->getData()) {
                return $this->redirectToRoute('contact_index');
            }

            $startedAt = $form->has('form_started_at')
                ? (int) $form->get('form_started_at')->getData()
                : 0;

            if ($startedAt > 0 && (time() - $startedAt) < 3) {
                return $this->redirectToRoute('contact_index');
            }

            $limiter = $contactFormLimiter->create($request->getClientIp() ?? 'anon');

            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Je verstuurt te snel berichten. Probeer het later opnieuw.');

                return $this->redirectToRoute('contact_index');
            }

            $data = $form->getData();

            $entity = new ContactEntity();
            $entity->setName($data['name']);
            $entity->setEmail($data['email']);
            $entity->setPhone($data['phone'] ?? null);
            $entity->setSubject($data['subject'] ?? null);
            $entity->setMessage($data['message']);
            $entity->setSource($data['source'] ?? 'contact');
            $entity->setIpAddress($request->getClientIp());
            $entity->setUserAgent($request->headers->get('User-Agent'));

            $entityManager->persist($entity);
            $entityManager->flush();

            $adminMail = (new TemplatedEmail())
                ->from(new Address('no-reply@topbags.local', 'Topbags'))
                ->to(new Address('info@topbags.nl', 'Topbags'))
                ->replyTo(new Address($data['email'], $data['name']))
                ->subject('Nieuw contactbericht: ' . ($data['subject'] ?: 'Contactformulier'))
                ->htmlTemplate('email/contact_message.html.twig')
                ->context([
                    'data' => $data,
                ]);

            $mailer->send($adminMail);

            $autoReply = (new TemplatedEmail())
                ->from(new Address('info@topbags.nl', 'Topbags'))
                ->to(new Address($data['email'], $data['name']))
                ->subject('Bedankt voor je bericht')
                ->htmlTemplate('email/contact_auto_reply.html.twig')
                ->context([
                    'data' => $data,
                ]);

            $mailer->send($autoReply);

            $this->addFlash('success', 'Bedankt! Je bericht is verzonden.');

            return $this->redirectToRoute('contact_index');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}