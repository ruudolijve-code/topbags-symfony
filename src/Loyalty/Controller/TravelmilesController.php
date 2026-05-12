<?php

declare(strict_types=1);

namespace App\Loyalty\Controller;

use App\Marketing\Entity\TravelMilesMember;
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
    ): Response {
        $success = false;
        $error = null;

        if ($request->isMethod('POST')) {
            $firstName = trim((string) $request->request->get('firstName', ''));
            $lastName = trim((string) $request->request->get('lastName', ''));
            $email = mb_strtolower(trim((string) $request->request->get('email', '')));
            $consent = (bool) $request->request->get('consent');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Vul een geldig e-mailadres in.';
            } elseif (!$consent) {
                $error = 'Geef toestemming om je aan te melden voor Travelmiles.';
            } else {
                $existingMember = $entityManager
                    ->getRepository(TravelMilesMember::class)
                    ->findOneBy(['email' => $email]);

                if ($existingMember instanceof TravelMilesMember) {
                    $existingMember
                        ->setFirstName($firstName !== '' ? $firstName : $existingMember->getFirstName())
                        ->setLastName($lastName !== '' ? $lastName : $existingMember->getLastName())
                        ->setIsActive(true)
                        ->setConsentGivenAt(new \DateTimeImmutable())
                        ->setSource('travelmiles');

                    $success = true;
                } else {
                    $member = new TravelMilesMember();
                    $member
                        ->setFirstName($firstName !== '' ? $firstName : null)
                        ->setLastName($lastName !== '' ? $lastName : null)
                        ->setEmail($email)
                        ->setIsActive(true)
                        ->setVoucherSent(false)
                        ->setSource('travelmiles')
                        ->setConsentGivenAt(new \DateTimeImmutable());

                    $entityManager->persist($member);

                    $success = true;
                }

                $entityManager->flush();
            }
        }

        return $this->render('loyalty/travelmiles/index.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }
}