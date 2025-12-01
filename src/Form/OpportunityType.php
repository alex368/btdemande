<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpportunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
->add('leadSource', ChoiceType::class, [
    'label' => 'Source du lead',
    'choices' => [
        'Téléphone' => 'phone',
        'Email' => 'email',
        'Site Web' => 'website',
        'Réseaux sociaux' => 'social',
        'Référence' => 'referral',
        'Autre' => 'other',
    ],
    'placeholder' => 'Sélectionner une source',
    'attr' => ['class' => 'form-select'],
])
->add('stage', ChoiceType::class, [
    'label' => 'Étape',
    'choices' => [
        'Prospect' => 'prospect',
        'Qualification' => 'qualification',
        'Proposal / Quote' => 'proposal',
        'Négociation' => 'negotiation',
        'Gagné' => 'won',
        'Perdu' => 'lost',
    ],
    'placeholder' => 'Sélectionner une étape',
    'attr' => ['class' => 'form-select'],
])

            ->add('createdAt', DateTimeType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Opportunity::class,
        ]);
    }
}
