<?php

namespace App\Form;

use App\Entity\Opportunity;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpportunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
->add('leadSource', ChoiceType::class, [
    'label' => 'Source du lead',
    'choices' => Opportunity::getLeadSourceChoices(),
    'placeholder' => 'Sélectionner une source',
    'attr' => ['class' => 'form-select js-lead-source'],
])
->add('leadSourceDetail', TextType::class, [
    'label' => 'Préciser qui',
    'required' => false,
    'attr' => [
        'class' => 'form-control js-lead-source-detail',
        'placeholder' => 'Nom du partenaire / recommandation',
    ],
    'row_attr' => ['class' => 'js-lead-source-detail-row d-none'],
])
            ->add('commercialReferent', EntityType::class, [
                'class' => User::class,
                'choice_label' => static fn(User $user): string => trim(($user->getLastname() ?? '') . ' ' . ($user->getName() ?? '')) ?: (string) $user->getEmail(),
                'query_builder' => static function (\App\Repository\UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->where('u.roles LIKE :admin')
                        ->orWhere('u.roles LIKE :collaborator')
                        ->orWhere('u.roles LIKE :collaborateur')
                        ->setParameter('admin', '%"ROLE_ADMIN"%')
                        ->setParameter('collaborator', '%"ROLE_COLLABORATOR"%')
                        ->setParameter('collaborateur', '%"ROLE_COLLABORATEUR"%')
                        ->orderBy('u.lastname', 'ASC');
                },
                'placeholder' => 'Sélectionner un référent commercial',
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Opportunity::class,
        ]);
    }
}
