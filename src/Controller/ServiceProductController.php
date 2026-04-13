<?php

namespace App\Controller;

use App\Entity\ServiceProduct;
use App\Form\ServiceProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServiceProductController extends AbstractController
{
    #[Route('/service/product', name: 'app_service_product')]
    public function index(EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR')) {
            throw $this->createAccessDeniedException();
        }


        $serviceProduct = $em->getRepository(ServiceProduct::class)->findAll();

        return $this->render('service_product/index.html.twig', [
            'services' => $serviceProduct,
        ]);
    }


    #[Route('/service/product/{id}', name: 'app_service_product_show')]
    public function show(EntityManagerInterface $em, $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR')) {
            throw $this->createAccessDeniedException();
        }


        $serviceProduct = $em->getRepository(ServiceProduct::class)->find($id);

        return $this->render('service_product/show.html.twig', [
            'services' => $serviceProduct,
        ]);
    }


    #[Route('/add/service/product', name: 'app_service_product_add')]
    public function add(EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR')) {
            throw $this->createAccessDeniedException();
        }


        $serviceProduct = new ServiceProduct();
        // Logique pour gérer le formulaire et enregistrer le produit de service
        $form =  $this->createForm(ServiceProductType::class, $serviceProduct);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($serviceProduct);
            $em->flush();

            $this->addFlash('success', 'Le service a été créé avec succès.');
            return $this->redirectToRoute('app_service_product');
        }


        return $this->render('service_product/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }




    #[Route('/edit/service/product/{id}', name: 'app_service_product_edit')]
    public function edit(EntityManagerInterface $em, Request $request, $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR')) {
            throw $this->createAccessDeniedException();
        }

        $serviceProduct = $em->getRepository(ServiceProduct::class)->find($id);


        // Logique pour gérer le formulaire et enregistrer le produit de service
        $form =  $this->createForm(ServiceProductType::class, $serviceProduct);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($serviceProduct);
            $em->flush();

            $this->addFlash('success', 'Le service a été créé avec succès.');
            return $this->redirectToRoute('app_service_product');
        }


        return $this->render('service_product/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    #[Route('/service/product/{id}', name: 'app_service_product_delete', methods: ['POST'])]
    public function delete(
        ServiceProduct $serviceProduct,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR')) {
            throw $this->createAccessDeniedException();
        }
        // Sécurité CSRF
        if ($this->isCsrfTokenValid('delete-serviceProduct-' . $serviceProduct->getId(), $request->get('_token'))) {


            $em->remove($serviceProduct);
            $em->flush();

            $this->addFlash('success', 'La prestation a été supprimée avec succès.');

            return $this->redirectToRoute('app_service_product');
        }

        $this->addFlash('danger', 'Token CSRF invalide.');


        return $this->redirectToRoute('app_service_product');
    }
}
