<?php

declare(strict_types=1);

namespace App\Loyalty\Controller;

use App\Loyalty\Entity\TravelMilesMember;
use App\Marketing\Entity\NewsletterSubscription;
use App\Loyalty\Service\NewsletterSubscriptionSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TravelmilesController extends AbstractController
{
    #[Route('/travelmiles', name: 'travelmiles_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        NewsletterSubscriptionSyncService $newsletterSubscriptionSyncService,
    ): Response {
        $success = false;
        $error = null;

        $formData = [
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'dateOfBirth' => '',
            'street' => '',
            'houseNumber' => '',
            'postalCode' => '',
            'city' => '',
            'country' => 'NL',
            'postalConsent' => false,
        ];

        if ($request->isMethod('POST')) {
            $formData = [
                'firstName' => trim((string) $request->request->get('firstName', '')),
                'lastName' => trim((string) $request->request->get('lastName', '')),
                'email' => mb_strtolower(trim((string) $request->request->get('email', ''))),
                'dateOfBirth' => trim((string) $request->request->get('dateOfBirth', '')),
                'street' => trim((string) $request->request->get('street', '')),
                'houseNumber' => trim((string) $request->request->get('houseNumber', '')),
                'postalCode' => trim((string) $request->request->get('postalCode', '')),
                'city' => trim((string) $request->request->get('city', '')),
                'country' => strtoupper(trim((string) $request->request->get('country', 'NL'))),
                'postalConsent' => (bool) $request->request->get('postalConsent'),
            ];

            $consent = (bool) $request->request->get('consent');
            $dateOfBirth = $this->createDateOfBirth($formData['dateOfBirth']);

            if ($formData['firstName'] === '') {
                $error = 'Vul je voornaam in.';
            } elseif ($formData['lastName'] === '') {
                $error = 'Vul je achternaam in.';
            } elseif ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Vul een geldig e-mailadres in.';
            } elseif ($dateOfBirth === null) {
                $error = 'Vul een geldige geboortedatum in.';
            } elseif (!$consent) {
                $error = 'Geef toestemming om je aan te melden voor Travelmiles.';
            } else {
                $existingMember = $entityManager
                    ->getRepository(TravelMilesMember::class)
                    ->findOneBy(['email' => $formData['email']]);

                $postalMailConsentAt = $formData['postalConsent']
                    ? new \DateTimeImmutable()
                    : null;

                if ($existingMember instanceof TravelMilesMember) {
                    $existingMember
                        ->setFirstName($formData['firstName'])
                        ->setLastName($formData['lastName'])
                        ->setDateOfBirth($dateOfBirth)
                        ->setStreet($formData['street'] !== '' ? $formData['street'] : null)
                        ->setHouseNumber($formData['houseNumber'] !== '' ? $formData['houseNumber'] : null)
                        ->setPostalCode($formData['postalCode'] !== '' ? $formData['postalCode'] : null)
                        ->setCity($formData['city'] !== '' ? $formData['city'] : null)
                        ->setCountry($formData['country'] !== '' ? $formData['country'] : 'NL')
                        ->setPostalMailConsentAt($postalMailConsentAt)
                        ->setIsActive(true)
                        ->setConsentGivenAt(new \DateTimeImmutable())
                        ->setSource('topbags_webshop');
                } else {
                    $member = new TravelMilesMember();

                    $member
                        ->setFirstName($formData['firstName'])
                        ->setLastName($formData['lastName'])
                        ->setEmail($formData['email'])
                        ->setDateOfBirth($dateOfBirth)
                        ->setStreet($formData['street'] !== '' ? $formData['street'] : null)
                        ->setHouseNumber($formData['houseNumber'] !== '' ? $formData['houseNumber'] : null)
                        ->setPostalCode($formData['postalCode'] !== '' ? $formData['postalCode'] : null)
                        ->setCity($formData['city'] !== '' ? $formData['city'] : null)
                        ->setCountry($formData['country'] !== '' ? $formData['country'] : 'NL')
                        ->setPostalMailConsentAt($postalMailConsentAt)
                        ->setIsActive(true)
                        ->setVoucherSent(false)
                        ->setSource('topbags_webshop')
                        ->setConsentGivenAt(new \DateTimeImmutable());

                    $entityManager->persist($member);
                }

                /*
                 * Voeg Travelmiles-aanmelding ook toe aan de centrale nieuwsbrieflijst.
                 * Belangrijk: de sync-service mag eerder uitgeschreven adressen niet opnieuw activeren.
                 */
                $newsletterSubscriptionSyncService->syncEmail(
                    $formData['email'],
                    NewsletterSubscription::SOURCE_TRAVELMILES_MEMBER
                );

                $entityManager->flush();

                $success = true;

                $formData = [
                    'firstName' => '',
                    'lastName' => '',
                    'email' => '',
                    'dateOfBirth' => '',
                    'street' => '',
                    'houseNumber' => '',
                    'postalCode' => '',
                    'city' => '',
                    'country' => 'NL',
                    'postalConsent' => false,
                ];
            }
        }

        return $this->render('loyalty/travelmiles/index.html.twig', [
            'success' => $success,
            'error' => $error,
            'formData' => $formData,
        ]);
    }

    private function createDateOfBirth(string $value): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        if ($date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }
}