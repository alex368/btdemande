<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product')]
    public function index(EntityManagerInterface $em): Response
    {

        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/product/add', name: 'app_product_create')]
    public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion des fichiers uploadés pour chaque document
            foreach ($form->get('documentTemplates') as $documentForm) {
                $uploadedFile = $documentForm->get('filename')->getData();

                if ($uploadedFile) {
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move(
                            $this->getParameter('documents_directory'), // défini dans services.yaml
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Erreur lors de l\'upload du fichier.');
                        return $this->redirectToRoute('app_product_create');
                    }

                    // Trouve l'objet DocumentTemplate correspondant
                    $document = $documentForm->getData();
                    $document->setTemplate($newFilename);
                }
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit enregistré');
            return $this->redirectToRoute('app_product');
        }

        return $this->render('product/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/{id}/edit', name: 'app_product_edit')]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer les fichiers uploadés
            foreach ($form->get('documentTemplates') as $documentForm) {
                $uploadedFile = $documentForm->get('filename')->getData();

                if ($uploadedFile) {
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move(
                            $this->getParameter('documents_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Erreur lors de l\'upload du fichier.');
                        return $this->redirectToRoute('app_product_edit', ['id' => $product->getId()]);
                    }

                    // Met à jour le template du document
                    $document = $documentForm->getData();
                    $document->setTemplate($newFilename);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Produit mis à jour avec succès.');
            return $this->redirectToRoute('app_product');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }


#[Route('/product/{id}/delete', name: 'app_product_delete', methods: ['POST', 'GET'])]
public function delete(
    Product $product,
    EntityManagerInterface $em,
    Request $request
): Response {
    // Protection CSRF (facultatif si suppression via lien GET)
    if ($request->isMethod('POST') && !$this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
        $this->addFlash('danger', 'Jeton CSRF invalide.');
        return $this->redirectToRoute('app_product');
    }

    $em->remove($product);
    $em->flush();

    $this->addFlash('success', 'Produit supprimé avec succès.');

    return $this->redirectToRoute('app_product');
}

#[Route('/product/{id}', name: 'app_product_show', methods: ['GET'])]
public function show(Product $product): Response
{
    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}


}
