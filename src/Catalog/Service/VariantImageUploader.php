<?php

declare(strict_types=1);

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class VariantImageUploader
{
    public function __construct(
        private readonly string $projectDir,
        private readonly VariantImagePathResolver $variantImagePathResolver,
    ) {
    }

    public function upload(
        UploadedFile $file,
        ProductVariant $variant
    ): string {
        /*
         * Gebruik model-SKU + kleurcode voor de afbeeldingsmap.
         *
         * Daardoor delen maatvarianten zoals:
         *
         * AW0AW18856-BDS-85
         * AW0AW18856-BDS-95
         * AW0AW18856-BDS-105
         *
         * allemaal dezelfde map:
         *
         * media/variants/a/w/AW0AW18856/BDS
         */
        $relativeDirectory =
            $this->variantImagePathResolver->directoryFromVariant($variant);

        if ($relativeDirectory === null) {
            throw new RuntimeException(sprintf(
                'Kan geen uploadpad bepalen voor variant SKU "%s". Controleer de model-SKU van het product en de supplier kleurcode van de variant.',
                $variant->getVariantSku()
            ));
        }

        $absoluteDirectory = sprintf(
            '%s/public/%s',
            rtrim($this->projectDir, '/'),
            ltrim($relativeDirectory, '/')
        );

        $this->ensureDirectoryExists($absoluteDirectory);

        $filename = $this->generateFilename($file);

        $file->move($absoluteDirectory, $filename);

        return $filename;
    }

    private function ensureDirectoryExists(
        string $absoluteDirectory
    ): void {
        if (is_dir($absoluteDirectory)) {
            return;
        }

        if (
            !mkdir($absoluteDirectory, 0775, true)
            && !is_dir($absoluteDirectory)
        ) {
            throw new RuntimeException(sprintf(
                'Kan map "%s" niet aanmaken.',
                $absoluteDirectory
            ));
        }
    }

    private function generateFilename(
        UploadedFile $file
    ): string {
        $originalName = pathinfo(
            $file->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        $safeName = $this->slugify($originalName);

        $extension = $file->guessExtension()
            ?: $file->getClientOriginalExtension()
            ?: 'jpg';

        $extension = strtolower(trim($extension, '.'));

        return sprintf(
            '%s-%s.%s',
            $safeName !== '' ? $safeName : 'image',
            substr(bin2hex(random_bytes(6)), 0, 12),
            $extension
        );
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));

        $value = preg_replace(
            '/[^a-z0-9]+/i',
            '-',
            $value
        ) ?? '';

        return trim($value, '-');
    }
}