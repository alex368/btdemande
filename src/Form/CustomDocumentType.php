<?php
namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('title', TextType::class, [
    'label' => 'Titre du document',
    'attr' => ['class' => 'form-control'],
    'row_attr' => ['class' => 'mb-3'],
    ])
    ->add('description', TextareaType::class, [
        'label' => 'Description',
        'required' => false,
        'attr' => ['class' => 'form-control', 'rows' => 4],
        'row_attr' => ['class' => 'mb-3'],
    ])
    ->add('comment', TextareaType::class, [
        'label' => 'commentaire',
        'required' => false,
        'attr' => ['class' => 'form-control', 'rows' => 4],
        'row_attr' => ['class' => 'mb-3'],
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
