<?php

namespace App\Form;

use App\Entity\DocumentTemplate;
use App\Entity\FundingMechanism;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
          ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('productDescription', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
              ->add('fundingMechanism', EntityType::class, [
    'class' => FundingMechanism::class,
    'choice_label' => 'name', // ou le champ à afficher
])

           ->add('documentTemplates', CollectionType::class, [
    'entry_type' => DocumentTemplateType::class,
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
    'prototype' => true,
    'entry_options' => ['label' => false],
           ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
