<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Contact;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
->add('type', ChoiceType::class, [
    'label' => 'Type d’activité',
    'choices' => [
        'Appel' => 'call',
        'Email' => 'email',
        'Réunion' => 'meeting',
        'Tâche' => 'task',
        'Rappel' => 'reminder',
        'Autre' => 'other',
    ],
    'placeholder' => 'Sélectionner un type',
    'attr' => ['class' => 'form-select'],
])

->add('status', ChoiceType::class, [
    'label' => 'Statut',
    'choices' => [
        'Ouvert' => 'open',
        'En cours' => 'in_progress',
        'Terminé' => 'done',
        'Annulé' => 'cancelled',
    ],
    'placeholder' => 'Sélectionner un statut',
    'attr' => ['class' => 'form-select'],
])

            ->add('description', null, [
    'label' => 'Description',
    'attr' => [
        'class' => 'form-control wysiwyg',
        'data-editor' => 'tinymce',
    ],
])

            ->add('activityDate', DateTimeType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
