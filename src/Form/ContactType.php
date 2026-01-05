<?php

namespace App\Form;

use App\Entity\Campany;
use App\Entity\Contact;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // CIVILITÉ
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

            // TÉLÉPHONE FIXE
            ->add('phone', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add'  => true,
                'allow_delete' => true,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^0[1-9](\s?\d{2}){4}$/',
                            'message' => 'Numéro fixe invalide (format FR attendu).'
                        ])
                    ],
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Numéro fixe']
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

            // RÉSEAUX SOCIAUX (URL)
            ->add('socialMedia', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add'  => true,
                'allow_delete' => true,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Regex([
                            'pattern' => '/^(https?:\/\/)?([\w.-]+)\.\w{2,}(\/.*)?$/i',
                            'message' => 'Lien de réseau social invalide.'
                        ])
                    ],
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Lien réseau social']
                ]
            ])

            // PAYS
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'required' => false,
                'placeholder' => 'Choisir un pays',
                'attr' => ['class' => 'form-select']
            ])

            // ADRESSES
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\' -]{2,60}$/u',
                        'message' => 'La ville est invalide.'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            // FONCTION
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

            // ENTREPRISE
            ->add('campany', EntityType::class, [
                'class' => Campany::class,
                'choice_label' => 'legalName',
                'required' => false,
                'placeholder' => 'Sélectionner une entreprise',
                'attr' => ['class' => 'form-select']
            ])

            // SITE WEB
            ->add('website', TextType::class, [
                'label' => 'Site Web',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '~^(https?://)?[a-z0-9.-]+\.[a-z]{2,6}(/.*)?$~i',
                        'message' => 'URL invalide.'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])

            // CODE POSTAL
            ->add('zipCode', TextType::class, [
                'label' => 'Code Postal',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Code postal invalide (5 chiffres).'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
