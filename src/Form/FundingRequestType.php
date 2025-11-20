<?php

namespace App\Form;

use App\Entity\Campany;
use App\Entity\FundingRequest;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FundingRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
               ->add('amount', MoneyType::class, [
                'label' => 'Montant demandé (€)',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => 'Ex : 15000',
                    'class' => 'form-control'
                ],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('user', EntityType::class, [
    'class' => User::class,
    'choice_label' => function (User $user) {
        return $user->getName(); // ou getUsername(), selon ton entité
    },
    'label' => 'Collaborateur',
    'query_builder' => function (EntityRepository $er) {
        return $er->createQueryBuilder('u')
            ->where('u.roles LIKE :role') // Si tu filtres par rôle
            ->setParameter('role', '%ROLE_COLLABORATOR%')
            ->orderBy('u.lastname', 'ASC');
    },
    'placeholder' => 'Sélectionnez un collaborateur',
    'attr' => [
        'class' => 'form-select'
    ],
    'row_attr' => ['class' => 'mb-3'],
])
            ->add('product', EntityType::class, [
                'label' => 'Produit de financement',
                'class' => Product::class,
                'choice_label' => 'name', // ou un autre champ significatif
                'placeholder' => 'Sélectionner un produit',
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FundingRequest::class,
        ]);
    }
}
