<?php

namespace App\Form;

use App\Entity\AddOnProduct;
use App\Entity\QuoteItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddOnProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'label' => 'Titre du produit complémentaire',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('description', null, [
                'label' => 'Description',
                'attr'  => [
                    'class' => 'form-control',
                    'rows'  => 3
                ],
            ])
            ->add('price', null, [
                'label' => 'Prix',
                'attr' => [
                    'class' => 'form-control addon-price'
                ]
            ])



            ->add('percentage', ChoiceType::class, [
                'label' => 'Pourcentage',
                'choices' => array_combine(
                    array_map(fn($i) => $i . ' %', range(0, 30, 5)),
                    range(0, 30, 5)
                ),
                'placeholder' => 'Sélectionnez un pourcentage',
                'attr' => [
                    'class' => 'form-control addon-percentage'
                ]
            ])


        ;;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddOnProduct::class,
        ]);
    }
}
