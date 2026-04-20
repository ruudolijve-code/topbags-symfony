<?php


namespace App\Guide\Service;

class TransportCtaResolver
{
    public function resolve(string $transport, string $bagType): ?array
    {
        return match (true) {
            $transport === 'car' && $bagType === 'duffle' => [
                'label' => 'Ideaal voor autoritten',
                'cta'   => 'Bekijk reistassen',
                'hint'  => 'Flexibel en makkelijk in te pakken',
            ],

            $transport === 'train' && $bagType === 'wheeled_bag' => [
                'label' => 'Perfect voor treinreizen',
                'cta'   => 'Bekijk tassen met wielen',
                'hint'  => 'Soepel over perrons en stations',
            ],

            $transport === 'plane' && $bagType === 'personal' => [
                'label' => 'Gegarandeerd onder stoel',
                'cta'   => 'Bekijk personal items',
                'hint'  => 'Voldoet aan airline-afmetingen',
            ],

            default => null,
        };
    }
}