<?php

namespace App\Controller\Admin;

use App\Entity\FundingMechanism;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FundingMechanismCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FundingMechanism::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // ✅ ID
            IdField::new('id')->hideOnForm(),

            // ✅ Name
            TextField::new('name', 'Name'),

            // ✅ Sector
            TextField::new('sector', 'Sector'),

            // ✅ Type (tu peux aussi le laisser en TextField si tu veux)
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Subvention' => 'subvention',
                    'Prêt' => 'loan',
                    'Concours' => 'contest',
                    'Incubation' => 'incubation',
                    'Autre' => 'other',
                ])
                ->renderExpanded(false)
                ->allowMultipleChoices(false),

            // ✅ Logo (image upload)
            ImageField::new('logo', 'Logo')
                ->setBasePath('/uploads/logos') // URL côté public
                ->setUploadDir('public/uploads/logos') // dossier réel
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false),
        ];
    }
}
