<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Quote;
use App\Form\QuoteType;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuoteController extends AbstractController
{
    #[Route('/quote/{id}', name: 'app_quote')]
    public function index(): Response
    {


        return $this->render('quote/index.html.twig', [
            'controller_name' => 'QuoteController',
        ]);
    }
#[Route('/quote/add/{id}', name: 'app_quote_add')]
public function add(
    Contact $contact,
    Request $request,
    EntityManagerInterface $em
): Response {
    // Création du devis
    $quote = new Quote();
    $quote->setCustomer($contact);
    $quote->setCreatedAt(new \DateTimeImmutable());

    // Génération auto du numéro de devis
    $lastQuote = $em->getRepository(Quote::class)->findOneBy([], ['id' => 'DESC']);
    $nextId = $lastQuote ? $lastQuote->getId() + 1 : 1;
    $quote->setQuoteNumber('Q-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT));

    // Création du formulaire
    $form = $this->createForm(QuoteType::class, $quote);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 TRÈS IMPORTANT : lier les QuoteItem au Quote
        foreach ($quote->getQuoteItems() as $item) {
            $item->setQuote($quote);

            // Lier chaque AddOn au QuoteItem
            foreach ($item->getAddOnProducts() as $addOn) {
                $addOn->setQuoteItem($item);
            }
        }

        $em->persist($quote);
        $em->flush();

        $this->addFlash('success', 'Le devis a été ajouté !');
        return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
    }

    return $this->render('quote/add.html.twig', [
        'form' => $form->createView(),
        'contact' => $contact,
    ]);
}




    #[Route('/quote/edit/{id}', name: 'app_quote_edit')]
    public function edit(): Response
    {


        return $this->render('quote/index.html.twig', [
            'controller_name' => 'QuoteController',
        ]);
    }


#[Route('/quote/show/{id}', name: 'app_quote_show')]
public function show(EntityManagerInterface $em, $id): Response
{
    $quote = $em->getRepository(Quote::class)->find($id);

    if (!$quote) {
        throw $this->createNotFoundException("Ce devis n'existe pas.");
    }

    $idContact = $quote->getCustomer()->getId(); // Charger le contact associé
    $contact = $em->getRepository(Contact::class)->find($idContact);

  
    
    return $this->render('quote/show.html.twig', [
        'quote' => $quote,
        'contact' => $contact,
    ]);
}


#[Route('/quote/pdf/{id}', name: 'app_quote_pdf')]
public function pdf(Quote $quote, PdfGenerator $pdfGenerator): Response
{
    $pdf = $pdfGenerator->generatePdf('quote/pdf.html.twig', [
        'quote' => $quote
    ]);

    return new Response($pdf, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="devis-' . $quote->getQuoteNumber() . '.pdf"'
    ]);
}





    #[Route('/quote/delete/{id}', name: 'app_quote_delete', methods: ['POST'])]
    public function delete(
        Quote $quote,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Sécurité CSRF
        if ($this->isCsrfTokenValid('delete-quote-' . $quote->getId(), $request->get('_token'))) {

            // Récupérer le contact avant suppression
            $contact = $quote->getCustomer();

            $em->remove($quote);
            $em->flush();

            $this->addFlash('success', 'Le devis a été supprimé avec succès.');

            return $this->redirectToRoute('app_contact_show', [
                'id' => $contact->getId(),
            ]);
        }

        $this->addFlash('danger', 'Token CSRF invalide.');

        return $this->redirectToRoute('app_quote_show', [
            'id' => $quote->getId(),
        ]);
    }
}
