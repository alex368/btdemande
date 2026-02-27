<?php

namespace App\Form;

use App\Entity\EventCustomer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventCustomerType extends AbstractType
{
    public function __construct(private SluggerInterface $slugger) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Startup Pitch Night',
                ],
                'row_attr' => ['class' => 'mb-3'],
            ])

            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'required' => false, // important
                'help' => 'Used in the event URL (e.g. startup-pitch-night)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'startup-pitch-night',
                ],
                'row_attr' => ['class' => 'mb-3'],
            ])

->add('theme', ChoiceType::class, [
    'label' => 'Thème',
    'choices' => [
        'Concours' => 'concours',
        'Rencontre' => 'rencontre',
        'Appel à projet' => 'appel-a-projet',
        'Autre' => 'Autre',
    ],
    'placeholder' => 'Sélectionnez un thème',
    'attr' => [
        'class' => 'form-control',
    ],
    'row_attr' => ['class' => 'mb-3'],
])

            ->add('startDate', DateTimeType::class, [
                'label' => 'Start date & time',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3'],
            ])

            ->add('endDate', DateTimeType::class, [
                'label' => 'End date & time',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3'],
            ])

            ->add('url', TextType::class, [
                'label' => 'Event website',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com',
                ],
                'row_attr' => ['class' => 'mb-3'],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control wysiwyg',
                    'rows' => 6,
                    'data-editor' => 'tinymce',
                    'placeholder' => 'Describe the event, agenda, speakers…',
                ],
                'row_attr' => ['class' => 'mb-4'],
            ])
        ;

        // Auto-slug si vide
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData(); // tableau brut du POST

            $title = trim((string)($data['title'] ?? ''));
            $slug  = trim((string)($data['slug'] ?? ''));

            if ($slug === '' && $title !== '') {
                $data['slug'] = strtolower($this->slugger->slug($title)->toString());
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventCustomer::class,
        ]);
    }
}
