<?php

namespace App\Form;

use App\Entity\FundingMechanism;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FundingMechanismType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
->add('name', TextType::class, [
    'label' => 'Name',
    'attr' => [
        'class' => 'form-control'
    ]
])

->add('sector', ChoiceType::class, [
    'label' => 'Sector',
    'choices' => [
        'Public' => 'public',
        'Private' => 'private',
        'NGO' => 'ngo',
        'Banking' => 'banking',
        'Other' => 'other',
    ],
    'placeholder' => 'Choose a sector',
    'attr' => [
        'class' => 'form-select',
    ],
])


->add('type', ChoiceType::class, [
    'label' => 'Type',
    'choices' => [
        'Public' => 'public',
        'Privé' => 'prive',
        'Banque' => 'banque',
        'Concours' => 'concours',
        'ONG' => 'ngo',
    ],
    'placeholder' => 'Choisir un type',
    'attr' => [
        'class' => 'form-select',
    ],
])



->add('description', TextareaType::class, [
    'label' => 'Description',
    'attr' => [
        'class' => 'form-control',
        'rows' => 4
    ]
])

->add('logo', FileType::class, [
    'label' => 'Logo',
    'required' => false,
    'mapped' => false,
    'attr' => [
        'class' => 'form-control'
    ]
])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FundingMechanism::class,
        ]);
    }
}
