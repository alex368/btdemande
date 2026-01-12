<?php

namespace App\Form;

use App\Entity\EventCustomer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventCustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Event title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Startup Pitch Night',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])

            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help' => 'Used in the event URL (e.g. startup-pitch-night)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'startup-pitch-night',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])

            ->add('theme', TextType::class, [
                'label' => 'Theme',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'AI, Fintech, SaaS…',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])

            ->add('startDate', DateTimeType::class, [
                'label' => 'Start date & time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])

            ->add('endDate', DateTimeType::class, [
                'label' => 'End date & time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])

            ->add('url', TextType::class, [
                'label' => 'Event website',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control wysiwyg',
                    'rows' => 6,
                    'data-editor' => 'tinymce',
                    'placeholder' => 'Describe the event, agenda, speakers…',
                ],
                'row_attr' => [
                    'class' => 'mb-4',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventCustomer::class,
        ]);
    }
}
