<?php

namespace App\Form;

use App\Entity\Funder;
use App\Entity\FundingMechanism;
use App\Entity\Partnership;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class PartnershipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salutation', ChoiceType::class, [
                'choices' => [
                    'Monsieur' => 'Mr',
                    'Madame'   => 'Mme',
                    'Autre'    => 'Autre',
                ],
                'label' => 'Civilité',
                'placeholder' => 'Sélectionner',
                'attr' => ['class' => 'form-select']
            ])

            ->add('occupation', TextType::class, [
                'label' => 'Metier / Fonction',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'La fonction est trop longue.'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])

            // NOM / PRÉNOM
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\' -]{2,40}$/u',
                        'message' => 'Le nom contient des caractères invalides.'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\' -]{2,40}$/u',
                        'message' => 'Le prénom contient des caractères invalides.'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])


            ->add('linkedin', TextType::class, [
                'label' => 'LinkedIn',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '#^(https?://)?(www\.)?linkedin\.com/(in|company)/[a-zA-Z0-9\-_%]+/?$#',
                        'message' => 'Veuillez entrer un lien LinkedIn valide (profil ou entreprise).'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])


            // EMAILS
            ->add('email', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add'  => true,
                'allow_delete' => true,
                'required' => false,
                'label' => false,
                'entry_options' => [
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
                            'message' => 'Adresse email invalide.'
                        ])
                    ],
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse email']
                ]
            ])

            // MOBILE
            ->add('mobilePhone', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add'  => true,
                'allow_delete' => true,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^(\+33|0)[67](\s?\d{2}){4}$/',
                            'message' => 'Numéro mobile invalide.'
                        ])
                    ],
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Téléphone mobile']
                ]
            ])



->add('fundingMechanism', EntityType::class, [
    'class' => FundingMechanism::class,
    'choice_label' => 'name',
    'label' => 'Financeur',
    'placeholder' => 'Sélectionner un financeur',
    'required' => false,
    'attr' => ['class' => 'form-select'],
])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partnership::class,
        ]);
    }
}
