<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\DocumentTemplate;
use App\Entity\FundingRequest;
use App\Entity\User;
use App\Form\CustomDocumentType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocumentController extends AbstractController
{

    #[Route('/documents/{id}', name: 'app_document_index', methods: ['GET', 'POST'])]
    public function index(
        FundingRequest $fundingRequest,
        EntityManagerInterface $em,
        Request $request,
        MailerService $mailerService
    ): Response {
        $userTest = $this->getUser(); // utilisateur connecté

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'validate') {
                $fundingRequest->setStatus('Attente client');
                // 🔹 Mail aux utilisateurs de la société liée à la demande
                $company = $fundingRequest->getCampany();
                if ($company) {
                    foreach ($company->getCustomer() as $user) {
                        if ($user->getEmail()) {
                            $mailerService->send(
                                $user->getEmail(),
                                'Votre dossier est en attente de complétude',
                                'emails/default.html.twig',
                                [
                                    'client'  => $user,
                                    'request' => $fundingRequest,
                                ]
                            );
                        }
                    }
                }

                // 🔹 Mail à l’utilisateur connecté
                if ($userTest instanceof User && $userTest->getEmail()) {
                    $mailerService->send(
                        $userTest->getEmail(),
                        'Vous avez validé un dossier',
                        'emails/default.html.twig',
                        [
                            'client'    => $userTest,
                            'request' => $fundingRequest,
                        ]
                    );
                }

                $em->flush();

                return $this->redirectToRoute('app_dashboard');
            }

            if ($action === 'save') {
                $fundingRequest->setStatus('En cours');
                $em->flush();

                return $this->redirectToRoute('app_document_index', [
                    'id' => $fundingRequest->getId()
                ]);
            }

            if ($action === 'cancel') {
                $fundingRequest->setStatus('En cours');
                $em->flush();

                return $this->redirectToRoute('app_dashboard');
            }
        }

        // 🔹 Documents déjà assignés
        $submittedDocuments = $fundingRequest->getDocuments();



        $test = $em->getRepository(Document::class)->findByFundingRequest($fundingRequest->getId());
        $documentMap = [];
        $customDocuments = [];

        foreach ($submittedDocuments as $doc) {

            if ($doc->getDocumentDefinition()) {
                $documentMap[$doc->getDocumentDefinition()->getId()] = $doc;
            } else {

                $customDocuments[] = $doc;
            }
        }

        $documentTemplates = $em->getRepository(DocumentTemplate::class)
            ->findBy(['product' => $fundingRequest->getProduct()]);



        return $this->render('document/index.html.twig', [
            'request'   => $fundingRequest,
            'templates' => $documentTemplates,
            'submitted' => $documentMap,
            'customs'   => $customDocuments,
            'test'      => $test,
        ]);
    }


    #[Route('/document/{id}/edit', name: 'document_edit')]
    public function edit(
        Document $document,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(CustomDocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Document modifié avec succès.');
            return $this->redirectToRoute('app_document_index', [
                'id' => $document->getFundingRequest()->getId(),
            ]);
        }

        return $this->render('document/edit.html.twig', [
            'form' => $form->createView(),
            'document' => $document,
        ]);
    }


    #[Route('/document/assign/{id}', name: 'document_assign')]
    public function assignTemplatesToRequest(
        FundingRequest $fundingRequest,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $product = $fundingRequest->getProduct();

        // Templates disponibles pour le produit
        $documentTemplates = $em->getRepository(DocumentTemplate::class)
            ->findBy(['product' => $product]);

        // Documents existants pour la demande
        $existingDocuments = $fundingRequest->getDocuments();

        // ID des templates déjà assignés
        $assignedTemplateIds = array_map(
            fn(Document $doc) => $doc->getDocumentDefinition()->getId(),
            array_filter(
                $existingDocuments->toArray(),
                fn(Document $doc) => $doc->getDocumentDefinition() !== null
            )
        );


        if ($request->isMethod('POST')) {
            $selectedIds = array_map('intval', $request->request->all('templates', []));

            // Supprimer les documents désélectionnés
            // Supprimer les documents désélectionnés
            foreach ($existingDocuments as $doc) {
                $definition = $doc->getDocumentDefinition();

                // si document lié à un template ET non sélectionné => supprimer
                if ($definition !== null && !in_array($definition->getId(), $selectedIds, true)) {
                    $em->remove($doc);
                }
            }


            // Ajouter les nouveaux documents sélectionnés
            // Ajouter les nouveaux documents sélectionnés
            foreach ($selectedIds as $templateId) {
                if (!in_array($templateId, $assignedTemplateIds, true)) {
                    $template = $em->getRepository(DocumentTemplate::class)->find($templateId);
                    if ($template) {
                        $doc = new Document();
                        $doc->setFundingRequest($fundingRequest);
                        $doc->setDocumentDefinition($template);
                        $doc->setFilename(''); // fichier non fourni encore
                        $doc->setStatus(false); // fichier non fourni encore
                        $doc->setDescription($template->getDescription());
                        $doc->setTitle($template->getTitle());

                        $em->persist($doc);
                    }
                }
            }


            $em->flush();

            $this->addFlash('success', 'Documents assignés avec succès.');
            return $this->redirectToRoute('app_document_index', ['id' => $fundingRequest->getId()]);
        }

        return $this->render('document/assign.html.twig', [
            'request' => $fundingRequest,
            'templates' => $documentTemplates,
            'assigned' => $assignedTemplateIds,
        ]);
    }

    #[Route('/document/delete/{id}', name: 'document_delete')]
    public function delete(Document $document, EntityManagerInterface $em): Response
    {
        $fundingRequestId = $document->getFundingRequest()->getId();

        $em->remove($document);
        $em->flush();

        $this->addFlash('success', 'Document supprimé.');
        return $this->redirectToRoute('app_document_index', ['id' => $fundingRequestId]);
    }

    #[Route('/document/refuse/{id}', name: 'document_refuse')]
    public function refuser(Document $document, EntityManagerInterface $em): Response
    {
        $fundingRequestId = $document->getFundingRequest()->getId();


        $document->setStatus(false);

        $em->flush();



        $this->addFlash('success', 'Document supprimé.');
        return $this->redirectToRoute('app_document_index', ['id' => $fundingRequestId]);
    }

    #[Route('/document/validate/{id}', name: 'document_validate')]
    public function valider(Document $document, EntityManagerInterface $em): Response
    {
        $fundingRequestId = $document->getFundingRequest()->getId();


        $document->setStatus(true);

        $em->flush();



        $this->addFlash('success', 'Document supprimé.');
        return $this->redirectToRoute('app_document_index', ['id' => $fundingRequestId]);
    }


    #[Route('/client/document/upload/{id}', name: 'client_document_upload')]
    public function uploadDocument(
        Document $document,
        Request $request,
        EntityManagerInterface $em
    ): Response {


        $form = $this->createForm(\App\Form\DocumentUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile) {
                $filename = uniqid() . '.' . $uploadedFile->guessExtension();
                $uploadedFile->move($this->getParameter('documents_directory'), $filename);

                $document->setFilename($filename);
                $em->persist($document);
                $em->flush();

                $this->addFlash('success', 'Fichier envoyé avec succès.');
            }

            return $this->redirectToRoute('client_documents', [
                'id' => $document->getFundingRequest()->getId(),
            ]);
        }

        return $this->render('document/upload.html.twig', [
            'form' => $form->createView(),
            'document' => $document,
        ]);
    }


    #[Route('/client/documents/{id}', name: 'client_documents', methods: ['GET', 'POST'])]
    public function clientDocuments(FundingRequest $fundingRequest, Request $request, EntityManagerInterface $em, $id): Response
    {



       $fundingRequest = $em->getRepository(FundingRequest::class)->find($id);
       $company = $fundingRequest->getCampany();
        $customer = $company->getCustomer();

        $users = [];

        foreach ($customer as $client) {
            $users = $client;
        }
 

        $userConnected = $this->getUser();

        if( $userConnected !== $users) {
            $this->addFlash('error', 'Accès refusé aux documents de cette demande.');
            return $this->redirectToRoute('app_dashboard');
        }



      
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'validate') {
                $fundingRequest->setStatus('Retour client');

                $em->flush();

                $this->addFlash('success', 'Vos documents ont été validés et envoyés.');
                return $this->redirectToRoute('app_dashboard'); // ou une autre route pour le client
            }
        }

        return $this->render('document/documents.html.twig', [
            'request'   => $fundingRequest,
            'documents' => $em->getRepository(Document::class)->findBy(['status' => false]),
        ]);
    }



    #[Route('/document/{id}/add-custom', name: 'document_add_custom')]
    public function addCustomDocument(
        FundingRequest $fundingRequest,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $document = new Document();
        $document->setFundingRequest($fundingRequest);
        $document->setFilename(''); // en attente d’upload
        $document->setStatus(false);
        $form = $this->createForm(\App\Form\CustomDocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($document);
            $em->flush();

            $this->addFlash('success', 'Document personnalisé ajouté avec succès.');
            return $this->redirectToRoute('app_document_index', ['id' => $fundingRequest->getId()]);
        }

        return $this->render('document/add_custom.html.twig', [
            'request' => $fundingRequest,
            'form' => $form->createView(),
        ]);
    }
}



