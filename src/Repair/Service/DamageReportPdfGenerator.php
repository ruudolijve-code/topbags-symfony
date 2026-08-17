<?php

declare(strict_types=1);

namespace App\Repair\Service;

use App\Repair\Entity\DamageReport;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

final class DamageReportPdfGenerator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly KernelInterface $kernel,
        private readonly DamageReportImageUploader $imageUploader,
    ) {
    }

    public function generate(DamageReport $report): string
    {
        $publicDir = $this->kernel
            ->getProjectDir() . '/public';

        $logoDataUri = $this->imageToDataUri(
            $publicDir . '/images/topbags.png'
        );

        /*
         * Zet bijvoorbeeld hier een transparante PNG
         * van de handtekening neer.
         */
        $signatureDataUri = $this->imageToDataUri(
            $publicDir . '/images/signatures/report-signature.png'
        );

        $photoDataUris = [];

        foreach ($report->getImages() as $image) {
            $path = $this->imageUploader->getAbsolutePath(
                $report,
                $image->getFilename()
            );

            if ($path === null) {
                continue;
            }

            $dataUri = $this->imageToDataUri($path);

            if ($dataUri !== null) {
                $photoDataUris[] = $dataUri;
            }
        }

        $html = $this->twig->render(
            'repair/damage_report/pdf.html.twig',
            [
                'report' => $report,
                'logoDataUri' => $logoDataUri,
                'signatureDataUri' => $signatureDataUri,
                'photoDataUris' => $photoDataUris,
            ]
        );

        $options = new Options();

        $options->set(
            'defaultFont',
            'DejaVu Sans'
        );

        $options->set(
            'isRemoteEnabled',
            false
        );

        $options->set(
            'chroot',
            $publicDir
        );

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml(
            $html,
            'UTF-8'
        );

        $dompdf->setPaper(
            'A4',
            'portrait'
        );

        $dompdf->render();

        return $dompdf->output();
    }

    private function imageToDataUri(
        string $path
    ): ?string {
        if (
            !is_file($path)
            || !is_readable($path)
        ) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $mimeType = mime_content_type($path);

        if ($mimeType === false) {
            return null;
        }

        return sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode($contents)
        );
    }
}