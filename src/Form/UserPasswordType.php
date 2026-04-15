<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'form-label text-uppercase small fw-bold'],
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe actuel est requis.']),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['class' => 'form-control'],
                    'label_attr' => ['class' => 'form-label text-uppercase small fw-bold'],
                    'constraints' => [
                        new NotBlank(['message' => 'Le nouveau mot de passe est requis.']),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Le mot de passe doit contenir au moins 8 caractères.',
                        ]),
                        new Regex([
                            'pattern' => '/[A-Z]/',
                            'message' => 'Le mot de passe doit contenir au moins une majuscule.',
                        ]),
                        new Regex([
                            'pattern' => '/[a-z]/',
                            'message' => 'Le mot de passe doit contenir au moins une minuscule.',
                        ]),
                        new Regex([
                            'pattern' => '/\d/',
                            'message' => 'Le mot de passe doit contenir au moins un chiffre.',
                        ]),
                        new Regex([
                            'pattern' => '/[\W_]/',
                            'message' => 'Le mot de passe doit contenir au moins un caractère spécial.',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => ['class' => 'form-control'],
                    'label_attr' => ['class' => 'form-label text-uppercase small fw-bold'],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
