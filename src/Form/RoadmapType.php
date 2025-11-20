<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Roadmap;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoadmapType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
 $builder
    ->add('date', null, [
        'label' => 'Date',
        'widget' => 'single_text',
        'attr' => [
            'class' => 'form-control',
        ],
        'row_attr' => [
            'class' => 'mb-3',
        ],
    ])
    ->add('product', EntityType::class, [
        'class' => Product::class,
        'choice_label' => 'name',
        'label' => 'Produit',
        'placeholder' => 'Sélectionnez un produit',
        'attr' => [
            'class' => 'form-select',
        ],
        'row_attr' => [
            'class' => 'mb-3',
        ],
    ]);


        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Roadmap::class,
        ]);
    }
}
