<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // ✅ ID visible seulement en liste / détail
            IdField::new('id')->hideOnForm(),

            // ✅ Name
            TextField::new('name', 'Name'),

            // ✅ Product Description
            TextEditorField::new('description', 'Product Description'),
        ];
    }
}
