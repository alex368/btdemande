<?php

namespace App\Form;

use App\Entity\Campany;
use App\Entity\Contact;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salutation', ChoiceType::class, [
                'label' => 'Civilité',
                'choices' => [
                    'Monsieur' => 'M.',
                    'Madame' => 'Mme',
                    'Mademoiselle' => 'Mlle',
                ],
                'placeholder' => 'Sélectionner une civilité',
                'attr' => ['class' => 'form-select'],
            ])

            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[\p{L}\s\-\'’]{1,50}$/u',
                        'message' => 'Le nom ne doit contenir que des lettres.',
                    ])
                ]
            ])

            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[\p{L}\s\-\'’]{1,50}$/u',
                        'message' => 'Le prénom ne doit contenir que des lettres.',
                    ])
                ]
            ])

            // -----------------------------------------------
            // EMAILS
            // -----------------------------------------------
            ->add('email', CollectionType::class, [
                'entry_type' => EmailType::class,
                'entry_options' => [
                    'label' => false,
                    'row_attr' => ['class' => 'no-label'], // 🔥 empêche le label du prototype
                    'attr' => ['class' => 'form-control mb-2'],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,              // 🔥 important : pas de label Symfony
                'prototype' => true,
                'prototype_name' => '__name__',
            ])

            // -----------------------------------------------
            // PHONES
            // -----------------------------------------------
            ->add('phone', CollectionType::class, [
                'entry_type' => TextType::class,
                'entry_options' => [
                    'label' => false,
                    'row_attr' => ['class' => 'no-label'], // 🔥
                    'attr' => ['class' => 'form-control mb-2'],
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^\+?[0-9\s\-]{6,20}$/',
                            'message' => 'Le numéro de téléphone est invalide.',
                        ])
                    ]
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
            ])

            // -----------------------------------------------
            // MOBILE PHONES
            // -----------------------------------------------
            ->add('mobilePhone', CollectionType::class, [
                'entry_type' => TextType::class,
                'entry_options' => [
                    'label' => false,
                    'row_attr' => ['class' => 'no-label'], // 🔥 obligatoire
                    'attr' => ['class' => 'form-control mb-2'],
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^\+?[0-9\s\-]{6,20}$/',
                            'message' => 'Le numéro mobile est invalide.',
                        ])
                    ]
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
            ])

            // -----------------------------------------------
            // SOCIAL MEDIA
            // -----------------------------------------------
            ->add('socialMedia', CollectionType::class, [
                'entry_type' => TextType::class,
                'entry_options' => [
                    'label' => false,
                    'row_attr' => ['class' => 'no-label'], // 🔥 supprime label prototype
                    'attr' => ['class' => 'form-control mb-2'],
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^(https?:\/\/)?([\w.-]+)\.[a-z]{2,}(\/.*)?$/i',
                            'message' => 'Le lien du réseau social est invalide.',
                        ])
                    ]
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
            ])

            // -----------------------------------------------
            // ENTREPRISE
            // -----------------------------------------------
            ->add('campany', EntityType::class, [
                'class' => Campany::class,
                'choice_label' => 'legalName',
                'placeholder' => 'Choisir une entreprise',
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])

            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[\p{L}\s\-]{2,50}$/u',
                        'message' => 'Le pays doit uniquement contenir des lettres.',
                    ])
                ]
            ])

            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[\p{L}\s\-]{2,50}$/u',
                        'message' => 'La ville doit uniquement contenir des lettres.',
                    ])
                ]
            ])

            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9\p{L}\s\-\'’,.]{3,100}$/u',
                        'message' => 'Adresse invalide.',
                    ])
                ]
            ])

            ->add('occupation', TextType::class, [
                'label' => 'Métier',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[\p{L}\s\-\'’]{2,50}$/u',
                        'message' => 'Le métier doit uniquement contenir des lettres.',
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
