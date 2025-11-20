<?php

namespace App\Form;
// src/Form/UserType.php

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, EmailType, PasswordType, TelType, ChoiceType
};
use Symfony\Component\Validator\Constraints\{
    NotBlank, Length, Regex, Email as EmailConstraint
};
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control border-0 rounded-0 shadow py-3 px-4 fs-5 mb-3',
                    'autocomplete' => 'name',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le nom est requis.'),
                    new Regex(pattern: '/^[\p{L}\s\-]+$/u', message: 'Le nom ne doit contenir que des lettres.')
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control border-0 rounded-0 shadow py-3 px-4 fs-5 mb-3',
                    'autocomplete' => 'given-name',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le prénom est requis.'),
                    new Regex(pattern: '/^[\p{L}\s\-]+$/u', message: 'Le prénom ne doit contenir que des lettres.')
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control border-0 rounded-0 shadow py-3 px-4 fs-5 mb-3',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank(message: 'L’email est requis.'),
                    new EmailConstraint(message: 'Email invalide.')
                ],
            ])
            ->add('number', TelType::class, [
                'label' => 'Numéro',
                'attr' => [
                    'class' => 'form-control border-0 rounded-0 shadow py-3 px-4 fs-5 mb-3',
                    'autocomplete' => 'tel',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le numéro est requis.'),
                    new Regex(pattern: '/^0[1-9](\d{2}){4}$/', message: 'Numéro invalide (format FR attendu).')
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control border-0 rounded-0 shadow py-3 px-4 fs-5 mb-3',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le mot de passe est requis.'),
                    new Length(min: 8, minMessage: 'Au moins 8 caractères.'),
                    new Regex(pattern: '/[A-Z]/', message: 'Au moins une majuscule.'),
                    new Regex(pattern: '/[a-z]/', message: 'Au moins une minuscule.'),
                    new Regex(pattern: '/\d/', message: 'Au moins un chiffre.'),
                    new Regex(pattern: '/[\W]/', message: 'Au moins un caractère spécial.')
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
