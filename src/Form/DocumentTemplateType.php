<?php

namespace App\Form;

use App\Entity\DocumentTemplate;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du document',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (facultative)',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
       ->add('filename', FileType::class, [
    'label' => 'Fichier (PDF, JPG...)',
    'mapped' => false,
    'required' => false,
    'attr' => ['class' => 'form-control'], // applique la classe Bootstrap
    'row_attr' => ['class' => 'mb-3'],     // ajoute un spacing vertical autour du champ
    'constraints' => [
        new File([
            'maxSize' => '5M',
'mimeTypes' => [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/zip',
    'application/x-rar-compressed',
    'application/x-7z-compressed',
    'application/x-tar',
    'application/x-bzip',
    'application/x-bzip2',
    'application/x-iso9660-image',
],
            'mimeTypesMessage' => 'Merci d\'uploader un PDF ou une image valide.',
        ])
    ],
])


        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentTemplate::class,
        ]);
    }
}
