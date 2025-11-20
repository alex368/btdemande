<?php
namespace App\Form;

use App\Entity\Campany;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class CampanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                    ->add('logo', FileType::class, [
                'label' => 'Company Logo (JPEG, PNG file)',
                'mapped' => false, // Important if logo is not mapped to an entity field directly
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG or PNG)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('legalName', TextType::class, [
                'label' => 'Raison social',
                'attr' => [
                    'id' => 'campany_legalName',
                    'placeholder' => 'Enter the legal name',
                    'class' => 'form-control'
                ],
                'row_attr' => ['class' => 'mb-3'],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2])
                ]
            ])
        ->add('sector', ChoiceType::class, [
    'label' => 'Secteur',
    'choices' => [
        'Biotech' => 'biotech',
        'Fintech' => 'fintech',
        'Entreprise' => 'entreprise',
        'HRTech' => 'hrtech',
        'EdTech' => 'edtech',
        'LegalTech' => 'legaltech',
        'Retail' => 'retail',
        'AI / Data' => 'ai',
        'Other' => 'other',
    ],
    'placeholder' => 'Choose a sector',
    'attr' => [
        'id' => 'campany_sector',
        'class' => 'form-select'
    ],
    'row_attr' => ['class' => 'mb-3'],
    'constraints' => [
        new NotBlank()
    ]
])
            ->add('adress', TextType::class, [
                'label' => 'Address',
                'attr' => [
                    'id' => 'campany_adress',
                    'placeholder' => 'Adresse',
                    'class' => 'form-control'
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('siren', TextType::class, [
                'label' => 'SIREN',
                'attr' => [
                    'id' => 'campany_siren',
                    'placeholder' => '9-digit SIREN number',
                    'class' => 'form-control'
                ],
                'row_attr' => ['class' => 'mb-3'],
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => '/^\d{9}$/',
                        'message' => 'The SIREN must be exactly 9 digits.',
                    ])
                ]
            ])
            ->add('creationDate', DateType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
                'attr' => [
                    'id' => 'campany_creationDate',
                    'class' => 'form-control',
                    'placeholder' => 'Select a date'
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
     ->add('stage', ChoiceType::class, [
    'label' => 'Stade de l\'entreprise',
    'choices' => [
        'Idéation' => 'ideation',
        'Croissance' => 'croissance',
        'Développement' => 'developpement',
        'Maturité' => 'maturite',
        'Déclin / Repositionnement' => 'declin',
    ],
    'placeholder' => 'Choisir un stade',
    'attr' => [
        'id' => 'campany_stage',
        'class' => 'form-select'
    ],
    'row_attr' => ['class' => 'mb-3'],
    'constraints' => [
        new NotBlank()
    ]
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Campany::class,
        ]);
    }
}
