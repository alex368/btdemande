<?php

namespace App\Controller\Admin;

use App\Entity\Campany;
use App\Entity\FundingMechanism;
use App\Entity\LegalPage;
use App\Entity\LegalPageSection;
use App\Entity\Partnership;
use App\Entity\Product;
use App\Entity\PromoCode;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{

    public function index(): Response
    {
        // ✅ Redirection par défaut vers la gestion des utilisateurs
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect(
            $adminUrlGenerator->setController(UserCrudController::class)->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Nexuss');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Mécanismes de financement', 'fas fa-money-bill-wave', FundingMechanism::class);
        yield MenuItem::linkToCrud('Partenaires', 'fas fa-handshake', Partnership::class);
        yield MenuItem::linkToCrud('Produits', 'fas fa-box', Product::class);
         yield MenuItem::linkToCrud('Entreprises', 'fas fa-building', Campany::class);

    }
}
