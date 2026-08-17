<?php

declare(strict_types=1);

namespace App\Repair\Form;

use App\Repair\Entity\DamageReportImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class DamageReportImageType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('uploadedFile', FileType::class, [
                'label' => 'Schadefoto',
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '8M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Upload een JPG-, PNG- of WebP-afbeelding.',
                    ),
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Volgorde',
                'required' => true,
                'empty_data' => '0',
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver,
    ): void {
        $resolver->setDefaults([
            'data_class' => DamageReportImage::class,
        ]);
    }
}