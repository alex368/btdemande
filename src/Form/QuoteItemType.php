<?php

namespace App\Form;

use App\Entity\AddOnProduct;
use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Entity\ServiceProduct;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
->add('productService', EntityType::class, [
    'class' => ServiceProduct::class,
    'choice_label' => 'title',
    'label' => 'Produit / Service',
    'attr' => [
        'class' => 'form-select product-price-selector'
    ],
    'choice_attr' => function(ServiceProduct $product) {
        return ['data-price' => $product->getPrice()];
    }
])

            ->add('addOnProducts', CollectionType::class, [
                'entry_type' => AddOnProductType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteItem::class,
        ]);
    }
}
