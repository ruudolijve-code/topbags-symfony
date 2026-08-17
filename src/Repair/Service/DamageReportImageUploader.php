<?php

declare(strict_types=1);

namespace App\Repair\Service;

use App\Repair\Entity\DamageReport;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

final class DamageReportImageUploader
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    public function upload(
        UploadedFile $file,
        DamageReport $report,
        int $position,
    ): string {
        if ($report->getId() === null) {
            throw new \LogicException(
                'Een schadefoto kan pas worden opgeslagen nadat het rapport is opgeslagen.'
            );
        }

        $directory = sprintf(
            '%s/public/uploads/damage-reports/%d',
            $this->kernel->getProjectDir(),
            $report->getId(),
        );

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf(
                    'Uploadmap "%s" kon niet worden aangemaakt.',
                    $directory,
                ));
            }
        }

        $extension = $file->guessExtension();

        if ($extension === null) {
            $extension = $file->getClientOriginalExtension();
        }

        $extension = strtolower($extension ?: 'jpg');

        $filename = sprintf(
            'damage-%02d-%s.%s',
            $position + 1,
            bin2hex(random_bytes(4)),
            $extension,
        );

        $file->move(
            $directory,
            $filename,
        );

        return $filename;
    }

    public function delete(
        DamageReport $report,
        string $filename,
    ): void {
        if ($report->getId() === null || $filename === '') {
            return;
        }

        $directory = sprintf(
            '%s/public/uploads/damage-reports/%d',
            $this->kernel->getProjectDir(),
            $report->getId(),
        );

        $path = $directory . '/' . basename($filename);

        if (is_file($path)) {
            @unlink($path);
        }

        if (
            is_dir($directory)
            && $this->isDirectoryEmpty($directory)
        ) {
            @rmdir($directory);
        }
    }

    private function isDirectoryEmpty(string $directory): bool
    {
        $files = scandir($directory);

        if ($files === false) {
            return false;
        }

        return count(array_diff(
            $files,
            ['.', '..']
        )) === 0;
    }

    public function getAbsolutePath(
        DamageReport $report,
        string $filename,
    ): ?string {
        if ($report->getId() === null || $filename === '') {
            return null;
        }

        $path = sprintf(
            '%s/public/uploads/damage-reports/%d/%s',
            $this->kernel->getProjectDir(),
            $report->getId(),
            basename($filename),
        );

        return is_file($path)
            ? $path
            : null;
    }
}