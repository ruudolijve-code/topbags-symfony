<?php

namespace App\Contact\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // =============================
            // Zichtbare velden
            // =============================

            ->add('name', TextType::class, [
                'label' => 'Naam',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 150),
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'E-mailadres',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])

            ->add('phone', TextType::class, [
                'label' => 'Telefoonnummer (optioneel)',
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 40),
                ],
            ])

            ->add('subject', ChoiceType::class, [
                'label' => 'Onderwerp',
                'required' => false,
                'choices' => [
                    'Productadvies'       => 'productadvies',
                    'Koffer huren'        => 'huren',
                    'Reparatie'           => 'reparatie',
                    'Zakelijke aanvraag'  => 'zakelijk',
                    'Overige vraag'       => 'overig',
                ],
                'placeholder' => 'Maak een keuze',
            ])

            ->add('message', TextareaType::class, [
                'label' => 'Bericht',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10),
                ],
                'attr' => [
                    'rows' => 6,
                ],
            ])

            ->add('source', HiddenType::class, [
                'required' => false,
            ])

            // =============================
            // 🔒 Anti-spam velden
            // =============================

            // Honeypot veld (bots vullen alles in)
            ->add('website', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'tabindex' => '-1',
                    'style' => 'position:absolute;left:-9999px;opacity:0;',
                ],
            ])

            // Timestamp veld (te snelle submit = bot)
            ->add('form_started_at', HiddenType::class, [
                'mapped' => false,
                'data' => time(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}