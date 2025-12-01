<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\Quote;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdAt', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de création',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('quoteNumber', TextType::class, [
                'label' => 'Numéro du devis',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('expirationDate', DateType::class, [
                'widget'  => 'single_text',
                'label'   => 'Date d\'expiration',
                'attr'    => ['class' => 'form-control'],
            ])

            //faire une partie recherche de client

            ->add('quoteItems', CollectionType::class, [
                'entry_type' => QuoteItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,   // OBLIGATOIRE !
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}
