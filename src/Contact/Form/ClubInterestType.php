<?php

namespace App\Contact\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClubInterestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organizationType', ChoiceType::class, [
                'label' => 'Type organisatie',
                'choices' => [
                    'Sportclub' => 'sportclub',
                    'School' => 'school',
                    'Vereniging' => 'vereniging',
                    'Anders' => 'anders',
                ],
                'placeholder' => 'Maak een keuze',
            ])
            ->add('organizationName', TextType::class, [
                'label' => 'Naam club of organisatie',
            ])
            ->add('contactName', TextType::class, [
                'label' => 'Contactpersoon',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mailadres',
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefoonnummer',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Plaats',
                'required' => false,
            ])
            ->add('memberCount', IntegerType::class, [
                'label' => 'Aantal leden / betrokkenen',
                'required' => false,
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Toelichting',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Vertel kort iets over jullie club, doelgroep of actie.',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}