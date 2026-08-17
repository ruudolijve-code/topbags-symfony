<?php

declare(strict_types=1);

namespace App\Repair\Controller;

use App\Repair\Entity\DamageReport;
use App\Repair\Service\DamageReportPdfGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DamageReportPdfController extends AbstractController
{
    #[Route(
        '/admin_dedtwaw/schaderapport/{id}/pdf',
        name: 'admin_damage_report_pdf',
        methods: ['GET']
    )]
    public function __invoke(
        DamageReport $report,
        DamageReportPdfGenerator $pdfGenerator,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pdf = $pdfGenerator->generate($report);

        return new Response(
            $pdf,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'inline; filename="%s.pdf"',
                    $report->getReportNumber()
                ),
            ]
        );
    }
}