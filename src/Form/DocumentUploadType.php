<?php
// src/Form/DocumentUploadType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', FileType::class, [
            'label' => 'Fichier',
            'required' => false,
            'mapped' => false, // car on gère manuellement le fichier
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // Pas besoin d'entité ici, formulaire manuel
    }
}
