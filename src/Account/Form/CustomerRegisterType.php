<?php

namespace App\Account\Form;

use App\Account\Entity\CustomerUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Voornaam',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Achternaam',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mailadres',
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefoonnummer',
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Wachtwoord',
                'mapped' => false,
            ])
            ->add('plainPasswordRepeat', PasswordType::class, [
                'label' => 'Herhaal wachtwoord',
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerUser::class,
        ]);
    }
}