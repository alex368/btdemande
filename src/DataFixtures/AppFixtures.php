<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\AddOnProduct;
use App\Entity\Campany;
use App\Entity\CampanyContact;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\DocumentTemplate;
use App\Entity\EventCustomer;
use App\Entity\Funder;
use App\Entity\FundingMechanism;
use App\Entity\FundingRequest;
use App\Entity\Opportunity;
use App\Entity\Partnership;
use App\Entity\Product;
use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Entity\Roadmap;
use App\Entity\ServiceProduct;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    private const IN_PROGRESS_REQUEST_COUNT = 29;
    private const VALIDATED_REQUEST_COUNT = 49;
    private const FUNDING_REQUEST_OWNER_IDS = [1, 2, 7, 8, 9, 10, 11, 12];

    public function load(ObjectManager $manager): void
    {
        $customers = [
            'alice' => $this->getReference(UserFixtures::CUSTOMER_ALICE, User::class),
            'bruno' => $this->getReference(UserFixtures::CUSTOMER_BRUNO, User::class),
            'chloe' => $this->getReference(UserFixtures::CUSTOMER_CHLOE, User::class),
            'diego' => $this->getReference(UserFixtures::CUSTOMER_DIEGO, User::class),
        ];
        $collaborators = $this->resolveFundingRequestOwners($manager);

        $bpifrance = $this->entity($manager, FundingMechanism::class, ['name' => 'Bpifrance Innovation'])
            ->setName('Bpifrance Innovation')
            ->setSector('Innovation')
            ->setType('Subvention')
            ->setDescription('Aides a l innovation et au developpement de projets technologiques.')
            ->setLogo('bpifrance.png')
            ->setCountry('France')
            ->setRegion('Ile-de-France')
            ->setCity('Paris')
            ->setAddress('27 avenue du General Leclerc')
            ->setPostalCode('94710')
            ->setProjectType('Innovation');
        $manager->persist($bpifrance);

        $ademe = $this->entity($manager, FundingMechanism::class, ['name' => 'ADEME Transition'])
            ->setName('ADEME Transition')
            ->setSector('Transition energetique')
            ->setType('Appel a projets')
            ->setDescription('Dispositifs pour la decarbonation, la sobriete et la transition industrielle.')
            ->setLogo('ademe.png')
            ->setCountry('France')
            ->setRegion('Auvergne-Rhone-Alpes')
            ->setCity('Lyon')
            ->setAddress('10 rue des Energies')
            ->setPostalCode('69000')
            ->setProjectType('Transition');
        $manager->persist($ademe);

        $funders = [
            $this->entity($manager, Funder::class, ['campanyName' => 'Bpifrance'])
                ->setCampanyName('Bpifrance')
                ->setDescription('Financeur public pour les projets d innovation et de croissance.'),
            $this->entity($manager, Funder::class, ['campanyName' => 'ADEME'])
                ->setCampanyName('ADEME')
                ->setDescription('Financeur public des projets lies a la transition environnementale.'),
        ];

        foreach ($funders as $funder) {
            $manager->persist($funder);
        }

        $products = [
            'diag' => $this->entity($manager, Product::class, ['name' => 'Diagnostic Innovation'])
                ->setName('Diagnostic Innovation')
                ->setProductDescription('Accompagnement a la structuration du projet et identification des guichets adaptes.')
                ->setFundingMechanism($bpifrance)
                ->setTypeProduct('Subvention'),
            'prototype' => $this->entity($manager, Product::class, ['name' => 'Prototype Industriel'])
                ->setName('Prototype Industriel')
                ->setProductDescription('Montage et suivi d un dossier de financement pour un prototype industriel.')
                ->setFundingMechanism($bpifrance)
                ->setTypeProduct('Prêt'),
            'decarbonation' => $this->entity($manager, Product::class, ['name' => 'Decarbonation Site'])
                ->setName('Decarbonation Site')
                ->setProductDescription('Aide au financement d un plan de decarbonation d un site de production.')
                ->setFundingMechanism($ademe)
                ->setTypeProduct('Prêt d\'honneur'),
            'expansion' => $this->entity($manager, Product::class, ['name' => 'Expansion Commerciale'])
                ->setName('Expansion Commerciale')
                ->setProductDescription('Financement de l acceleration commerciale et du developpement national.')
                ->setFundingMechanism($bpifrance)
                ->setTypeProduct('Appel a projets'),
        ];

        foreach ($products as $product) {
            $manager->persist($product);
        }

        $documentTemplates = [
            $this->entity($manager, DocumentTemplate::class, ['title' => 'Pitch deck'])
                ->setTitle('Pitch deck')
                ->setDescription('Presentation synthese du projet et de son marche.')
                ->setProduct($products['diag'])
                ->setTemplate('pitch-deck-template.docx'),
            $this->entity($manager, DocumentTemplate::class, ['title' => 'Business plan'])
                ->setTitle('Business plan')
                ->setDescription('Projection economique, scenario financier et feuille de route.')
                ->setProduct($products['prototype'])
                ->setTemplate('business-plan-template.docx'),
            $this->entity($manager, DocumentTemplate::class, ['title' => 'Budget previsionnel'])
                ->setTitle('Budget previsionnel')
                ->setDescription('Budget detaille du projet, avec CAPEX et OPEX.')
                ->setProduct($products['prototype'])
                ->setTemplate('budget-template.xlsx'),
            $this->entity($manager, DocumentTemplate::class, ['title' => 'Plan de transition'])
                ->setTitle('Plan de transition')
                ->setDescription('Plan d action, gains attendus et jalons environnementaux.')
                ->setProduct($products['decarbonation'])
                ->setTemplate('transition-plan-template.docx'),
            $this->entity($manager, DocumentTemplate::class, ['title' => 'Plan de croissance'])
                ->setTitle('Plan de croissance')
                ->setDescription('Projection commerciale et objectifs de developpement national.')
                ->setProduct($products['expansion'])
                ->setTemplate('growth-plan-template.docx'),
        ];

        foreach ($documentTemplates as $documentTemplate) {
            $manager->persist($documentTemplate);
        }

        $partnerships = [
            $this->entity($manager, Partnership::class, ['firstname' => 'Claire', 'lastname' => 'Roussel'])
                ->setLastname('Roussel')
                ->setFirstname('Claire')
                ->setSalutation('Madame')
                ->setLinkedin('https://www.linkedin.com/in/claire-roussel')
                ->setOccupation('Responsable partenariats innovation')
                ->setEmail(['claire.roussel@bpifrance.example'])
                ->setMobilePhone(['0611223344'])
                ->setFundingMechanism($bpifrance),
            $this->entity($manager, Partnership::class, ['firstname' => 'Leo', 'lastname' => 'Garcia'])
                ->setLastname('Garcia')
                ->setFirstname('Leo')
                ->setSalutation('Monsieur')
                ->setLinkedin('https://www.linkedin.com/in/leo-garcia')
                ->setOccupation('Chef de projet transition')
                ->setEmail(['leo.garcia@ademe.example'])
                ->setMobilePhone(['0677889900'])
                ->setFundingMechanism($ademe),
        ];

        foreach ($partnerships as $partnership) {
            $manager->persist($partnership);
        }

        $campanies = [
            'atelier' => $this->entity($manager, Campany::class, ['siren' => '812345671'])
                ->setLegalName('Atelier Nova')
                ->setSector('Industrie creative')
                ->setAdress('12 rue des Artisans, Nantes')
                ->setSiren('812345671')
                ->setCreationDate(new \DateTime('2021-03-15'))
                ->setStage('Amorcage')
                ->setLogo('atelier-nova.png')
                ->setProjetName('Nova Board'),
            'green' => $this->entity($manager, Campany::class, ['siren' => '812345672'])
                ->setLegalName('Green Pulse')
                ->setSector('Cleantech')
                ->setAdress('18 quai de la Loire, Lyon')
                ->setSiren('812345672')
                ->setCreationDate(new \DateTime('2020-06-10'))
                ->setStage('Acceleration')
                ->setLogo('green-pulse.png')
                ->setProjetName('Pulse Factory'),
            'quantum' => $this->entity($manager, Campany::class, ['siren' => '812345673'])
                ->setLegalName('Quantum Forge')
                ->setSector('Deeptech')
                ->setAdress('5 avenue Galilee, Toulouse')
                ->setSiren('812345673')
                ->setCreationDate(new \DateTime('2022-01-24'))
                ->setStage('Seed')
                ->setLogo('quantum-forge.png')
                ->setProjetName('Forge One'),
        ];

        $campanies['atelier']->addCustomer($customers['alice'])->addCustomer($customers['bruno']);
        $campanies['green']->addCustomer($customers['chloe']);
        $campanies['quantum']->addCustomer($customers['diego']);

        foreach ($campanies as $campany) {
            $manager->persist($campany);
        }

        $roadmaps = [
            (new Roadmap())
                ->setCampany($campanies['atelier'])
                ->setProduct($products['diag'])
                ->setDate(new \DateTime('2026-04-15')),
            (new Roadmap())
                ->setCampany($campanies['atelier'])
                ->setProduct($products['prototype'])
                ->setDate(new \DateTime('2026-05-20')),
            (new Roadmap())
                ->setCampany($campanies['green'])
                ->setProduct($products['decarbonation'])
                ->setDate(new \DateTime('2026-04-28')),
            (new Roadmap())
                ->setCampany($campanies['quantum'])
                ->setProduct($products['prototype'])
                ->setDate(new \DateTime('2026-06-02')),
        ];

        foreach ($roadmaps as $roadmap) {
            $manager->persist($roadmap);
        }

        $contacts = [
            'jeanne' => $this->entity($manager, Contact::class, ['firstName' => 'Jeanne', 'lastName' => 'Renaud'])
                ->setSalutation('Madame')
                ->setLastName('Renaud')
                ->setFirstName('Jeanne')
                ->setEmail(['jeanne.renaud@ateliernova.example'])
                ->setPhone(['0240123456'])
                ->setMobilePhone(['0610101010'])
                ->setSocialMedia(['linkedin' => 'https://linkedin.com/in/jeanne-renaud'])
                ->setCountry('France')
                ->setAdress('12 rue des Artisans')
                ->setCity('Nantes')
                ->setWebsite('https://ateliernova.example')
                ->setOccupation('CEO')
                ->setZipCode('44000')
                ->setAccount($customers['alice']),
            'malik' => $this->entity($manager, Contact::class, ['firstName' => 'Malik', 'lastName' => 'Benali'])
                ->setSalutation('Monsieur')
                ->setLastName('Benali')
                ->setFirstName('Malik')
                ->setEmail(['malik.benali@greenpulse.example'])
                ->setPhone(['0472001122'])
                ->setMobilePhone(['0620202020'])
                ->setSocialMedia(['linkedin' => 'https://linkedin.com/in/malik-benali'])
                ->setCountry('France')
                ->setAdress('18 quai de la Loire')
                ->setCity('Lyon')
                ->setWebsite('https://greenpulse.example')
                ->setOccupation('Directeur operations')
                ->setZipCode('69000')
                ->setAccount($customers['chloe']),
            'sarah' => $this->entity($manager, Contact::class, ['firstName' => 'Sarah', 'lastName' => 'Lopez'])
                ->setSalutation('Madame')
                ->setLastName('Lopez')
                ->setFirstName('Sarah')
                ->setEmail(['sarah.lopez@quantumforge.example'])
                ->setPhone(['0561003344'])
                ->setMobilePhone(['0630303030'])
                ->setSocialMedia(['linkedin' => 'https://linkedin.com/in/sarah-lopez'])
                ->setCountry('France')
                ->setAdress('5 avenue Galilee')
                ->setCity('Toulouse')
                ->setWebsite('https://quantumforge.example')
                ->setOccupation('Fondatrice')
                ->setZipCode('31000')
                ->setAccount($customers['diego']),
        ];

        foreach ($contacts as $contact) {
            $manager->persist($contact);
        }

        $campanyContacts = [
            $this->entity($manager, CampanyContact::class, ['siren' => '812345671'])
                ->setLegalName('Atelier Nova')
                ->setSector('Industrie creative')
                ->setAdress('12 rue des Artisans, Nantes')
                ->setSiren('812345671')
                ->setCreationDate(new \DateTime('2021-03-15'))
                ->setStage('Amorcage')
                ->setLogo('atelier-nova-contact.png')
                ->setProjectName('Nova Board'),
            $this->entity($manager, CampanyContact::class, ['siren' => '812345672'])
                ->setLegalName('Green Pulse')
                ->setSector('Cleantech')
                ->setAdress('18 quai de la Loire, Lyon')
                ->setSiren('812345672')
                ->setCreationDate(new \DateTime('2020-06-10'))
                ->setStage('Acceleration')
                ->setLogo('green-pulse-contact.png')
                ->setProjectName('Pulse Factory'),
        ];

        $campanyContacts[0]->addContact($contacts['jeanne']);
        $campanyContacts[0]->addContact($contacts['sarah']);
        $campanyContacts[1]->addContact($contacts['malik']);

        foreach ($campanyContacts as $campanyContact) {
            $manager->persist($campanyContact);
        }

        $opportunities = [
            $this->entity($manager, Opportunity::class, ['leadSource' => 'Salon Vivatech', 'contact' => $contacts['jeanne']])
                ->setLeadSource('Salon Vivatech')
                ->setStage('Qualifie')
                ->setCreatedAt(new \DateTimeImmutable('2026-03-01 09:00:00'))
                ->setUser($customers['alice'])
                ->setContact($contacts['jeanne']),
            $this->entity($manager, Opportunity::class, ['leadSource' => 'Recommandation partenaire', 'contact' => $contacts['malik']])
                ->setLeadSource('Recommandation partenaire')
                ->setStage('Proposition envoyee')
                ->setCreatedAt(new \DateTimeImmutable('2026-03-18 14:30:00'))
                ->setUser($customers['chloe'])
                ->setContact($contacts['malik']),
            $this->entity($manager, Opportunity::class, ['leadSource' => 'Inbound site web', 'contact' => $contacts['sarah']])
                ->setLeadSource('Inbound site web')
                ->setStage('Discovery')
                ->setCreatedAt(new \DateTimeImmutable('2026-03-23 11:15:00'))
                ->setUser($customers['diego'])
                ->setContact($contacts['sarah']),
        ];

        foreach ($opportunities as $opportunity) {
            $manager->persist($opportunity);
        }

        $activities = [
            $this->entity($manager, Activity::class, ['type' => 'Appel', 'contact' => $contacts['jeanne']])
                ->setType('Appel')
                ->setDescription('Premier cadrage du besoin de financement et validation des pieces attendues.')
                ->setActivityDate(new \DateTime('2026-03-03 10:00:00'))
                ->setStatus('Termine')
                ->setContact($contacts['jeanne']),
            $this->entity($manager, Activity::class, ['type' => 'Email', 'contact' => $contacts['jeanne']])
                ->setType('Email')
                ->setDescription('Transmission de la checklist documentaire et du retroplanning.')
                ->setActivityDate(new \DateTime('2026-03-05 16:30:00'))
                ->setStatus('Termine')
                ->setContact($contacts['jeanne']),
            $this->entity($manager, Activity::class, ['type' => 'Visio', 'contact' => $contacts['malik']])
                ->setType('Visio')
                ->setDescription('Revue du budget previsionnel et arbitrages sur les lots industriels.')
                ->setActivityDate(new \DateTime('2026-03-19 11:00:00'))
                ->setStatus('Planifie')
                ->setContact($contacts['malik']),
            $this->entity($manager, Activity::class, ['type' => 'Rappel', 'contact' => $contacts['sarah']])
                ->setType('Rappel')
                ->setDescription('Relance sur le pitch deck et la note d impact environnemental.')
                ->setActivityDate(new \DateTime('2026-03-24 09:30:00'))
                ->setStatus('A faire')
                ->setContact($contacts['sarah']),
        ];

        foreach ($activities as $activity) {
            $manager->persist($activity);
        }

        $fundingRequests = [
            'atelier' => $this->entity($manager, FundingRequest::class, ['campany' => $campanies['atelier'], 'product' => $products['prototype'], 'user' => $customers['alice']])
                ->setCampany($campanies['atelier'])
                ->setAmount(45000)
                ->setProduct($products['prototype'])
                ->setStatus(FundingRequest::STATUS_IN_PROGRESS)
                ->setUser($collaborators[0])
                ->setAssistant($collaborators[1])
                ->setComment('Dossier bien avance, il manque encore le budget detaille.')
                ->setDecision(null)
                ->setCreatedAt(new \DateTimeImmutable('2026-03-04 10:00:00')),
            'green' => $this->entity($manager, FundingRequest::class, ['campany' => $campanies['green'], 'product' => $products['decarbonation'], 'user' => $collaborators[1]])
                ->setCampany($campanies['green'])
                ->setAmount(90000)
                ->setProduct($products['decarbonation'])
                ->setStatus(FundingRequest::STATUS_WAITING_CLIENT)
                ->setUser($collaborators[1])
                ->setAssistant($collaborators[2])
                ->setComment('Attente des factures energie et du plan de charge usine.')
                ->setDecision(null)
                ->setCreatedAt(new \DateTimeImmutable('2026-03-12 15:00:00')),
            'quantum' => $this->entity($manager, FundingRequest::class, ['campany' => $campanies['quantum'], 'product' => $products['diag'], 'user' => $collaborators[2]])
                ->setCampany($campanies['quantum'])
                ->setAmount(120000)
                ->setProduct($products['diag'])
                ->setStatus(FundingRequest::STATUS_VALIDATED)
                ->setUser($collaborators[2])
                ->setAssistant($collaborators[0])
                ->setComment('Validation obtenue, lancement de la phase suivante.')
                ->setDecision(FundingRequest::DECISION_VALIDATED)
                ->setCreatedAt(new \DateTimeImmutable('2026-02-20 08:45:00')),
        ];

        foreach ($fundingRequests as $fundingRequest) {
            $manager->persist($fundingRequest);
        }

        $validatedProducts = [
            $products['diag'],
            $products['prototype'],
            $products['decarbonation'],
        ];
        $inProgressProducts = [
            $products['diag'],
            $products['prototype'],
            $products['decarbonation'],
            $products['expansion'],
        ];

        for ($index = 1; $index <= self::IN_PROGRESS_REQUEST_COUNT; ++$index) {
            $customer = $manager->getRepository(User::class)->findOneBy([
                'email' => sprintf('customer%03d@example.test', $index + 10),
            ]);

            if (!$customer instanceof User) {
                continue;
            }

            $campany = $this->entity($manager, Campany::class, [
                'siren' => sprintf('93%07d', $index),
            ]);
            $campany
                ->setLegalName(sprintf('Test Company %03d', $index))
                ->setSector($index % 2 === 0 ? 'Services numeriques' : 'Industrie')
                ->setAdress(sprintf('%d boulevard des Tests, Paris', 100 + $index))
                ->setSiren(sprintf('93%07d', $index))
                ->setCreationDate(new \DateTime(sprintf('202%d-%02d-%02d', $index % 4, (($index - 1) % 12) + 1, (($index - 1) % 27) + 1)))
                ->setStage($index % 3 === 0 ? 'Acceleration' : 'Amorcage')
                ->setLogo(sprintf('test-company-%03d.png', $index))
                ->setProjetName(sprintf('Projet Test %03d', $index))
                ->addCustomer($customer);
            $manager->persist($campany);

            $product = $inProgressProducts[$index % count($inProgressProducts)];
            $collaborator = $collaborators[$index % count($collaborators)];
            $assistant = $collaborators[($index + 1) % count($collaborators)];

            $request = $this->entity($manager, FundingRequest::class, [
                'campany' => $campany,
                'product' => $product,
                'user' => $collaborator,
            ]);
            $request
                ->setCampany($campany)
                ->setAmount(15000 + ($index * 2500))
                ->setProduct($product)
                ->setStatus(FundingRequest::STATUS_IN_PROGRESS)
                ->setUser($collaborator)
                ->setAssistant($assistant)
                ->setComment(sprintf('Demande de test generee automatiquement #%03d.', $index))
                ->setDecision(null)
                ->setCreatedAt((new \DateTimeImmutable('2026-01-08 09:00:00'))->modify(sprintf('+%d days', $index)));
            $manager->persist($request);

            $roadmap = $this->entity($manager, Roadmap::class, [
                'campany' => $campany,
                'product' => $product,
                'date' => new \DateTime(sprintf('2026-%02d-%02d', (($index + 1) % 12) + 1, (($index + 4) % 27) + 1)),
            ]);
            $roadmap
                ->setCampany($campany)
                ->setProduct($product)
                ->setDate(new \DateTime(sprintf('2026-%02d-%02d', (($index + 1) % 12) + 1, (($index + 4) % 27) + 1)));
            $manager->persist($roadmap);
        }

        for ($index = 1; $index <= self::VALIDATED_REQUEST_COUNT; ++$index) {
            $customer = $manager->getRepository(User::class)->findOneBy([
                'email' => sprintf('customer%03d@example.test', $index + 60),
            ]);

            if (!$customer instanceof User) {
                continue;
            }

            $campany = $this->entity($manager, Campany::class, [
                'siren' => sprintf('94%07d', $index),
            ]);
            $campany
                ->setLegalName(sprintf('Validated Company %03d', $index))
                ->setSector($index % 2 === 0 ? 'SaaS' : 'Industrie verte')
                ->setAdress(sprintf('%d avenue de la Validation, Lyon', 200 + $index))
                ->setSiren(sprintf('94%07d', $index))
                ->setCreationDate(new \DateTime(sprintf('202%d-%02d-%02d', $index % 4, (($index + 2) % 12) + 1, (($index + 8) % 27) + 1)))
                ->setStage($index % 2 === 0 ? 'Acceleration' : 'Croissance')
                ->setLogo(sprintf('validated-company-%03d.png', $index))
                ->setProjetName(sprintf('Validated Project %03d', $index))
                ->addCustomer($customer);
            $manager->persist($campany);

            $product = $validatedProducts[($index - 1) % count($validatedProducts)];
            $collaborator = $collaborators[($index + 1) % count($collaborators)];
            $assistant = $collaborators[($index + 2) % count($collaborators)];

            $request = $this->entity($manager, FundingRequest::class, [
                'campany' => $campany,
                'product' => $product,
                'user' => $collaborator,
            ]);
            $request
                ->setCampany($campany)
                ->setAmount(25000 + ($index * 3500))
                ->setProduct($product)
                ->setStatus(FundingRequest::STATUS_VALIDATED)
                ->setUser($collaborator)
                ->setAssistant($assistant)
                ->setComment(sprintf('Dossier valide sur l annee 2026 #%03d.', $index))
                ->setDecision(FundingRequest::DECISION_VALIDATED)
                ->setCreatedAt((new \DateTimeImmutable('2026-01-03 10:30:00'))->modify(sprintf('+%d days', $index * 2)));
            $manager->persist($request);

            $roadmap = $this->entity($manager, Roadmap::class, [
                'campany' => $campany,
                'product' => $product,
                'date' => new \DateTime(sprintf('2026-%02d-%02d', (($index + 5) % 12) + 1, (($index + 10) % 27) + 1)),
            ]);
            $roadmap
                ->setCampany($campany)
                ->setProduct($product)
                ->setDate(new \DateTime(sprintf('2026-%02d-%02d', (($index + 5) % 12) + 1, (($index + 10) % 27) + 1)));
            $manager->persist($roadmap);
        }

        $documents = [
            $this->entity($manager, Document::class, ['filename' => 'pitch-deck-atelier-nova.pdf'])
                ->setFilename('pitch-deck-atelier-nova.pdf')
                ->setDocumentDefinition($documentTemplates[0])
                ->setFundingRequest($fundingRequests['atelier'])
                ->setDescription('Pitch deck version client pour le projet Nova Board.')
                ->setTitle('Pitch deck Atelier Nova')
                ->setStatus(true)
                ->setComment('Version transmise et validee'),
            $this->entity($manager, Document::class, ['filename' => 'business-plan-atelier-nova.pdf'])
                ->setFilename('business-plan-atelier-nova.pdf')
                ->setDocumentDefinition($documentTemplates[1])
                ->setFundingRequest($fundingRequests['atelier'])
                ->setDescription('Business plan consolide avec scenario prudent et scenario cible.')
                ->setTitle('Business plan Atelier Nova')
                ->setStatus(false)
                ->setComment('En attente d’une mise à jour financière'),
            $this->entity($manager, Document::class, ['filename' => 'transition-plan-green-pulse.pdf'])
                ->setFilename('transition-plan-green-pulse.pdf')
                ->setDocumentDefinition($documentTemplates[3])
                ->setFundingRequest($fundingRequests['green'])
                ->setDescription('Plan de transition energetique du site de production.')
                ->setTitle('Plan de transition Green Pulse')
                ->setStatus(false)
                ->setComment('Pieces complementaires encore manquantes'),
            $this->entity($manager, Document::class, ['filename' => 'budget-quantum-forge.xlsx'])
                ->setFilename('budget-quantum-forge.xlsx')
                ->setDocumentDefinition($documentTemplates[2])
                ->setFundingRequest($fundingRequests['quantum'])
                ->setDescription('Budget detaille valide pour la phase diagnostic.')
                ->setTitle('Budget Quantum Forge')
                ->setStatus(true)
                ->setComment('Archive de reference'),
        ];

        foreach ($documents as $document) {
            $manager->persist($document);
        }

        $services = [
            'audit' => $this->entity($manager, ServiceProduct::class, ['title' => 'Audit financement'])
                ->setTitle('Audit financement')
                ->setDescription('Audit initial du projet, eligibilite et priorisation des aides.')
                ->setPrice(1490.0),
            'montage' => $this->entity($manager, ServiceProduct::class, ['title' => 'Montage dossier'])
                ->setTitle('Montage dossier')
                ->setDescription('Constitution du dossier, collecte des pieces et suivi du depot.')
                ->setPrice(3900.0),
        ];

        foreach ($services as $service) {
            $manager->persist($service);
        }

        $quotes = [
            'atelier' => $this->entity($manager, Quote::class, ['quoteNumber' => 'DEV-2026-001'])
                ->setCreatedAt(new \DateTimeImmutable('2026-03-06 13:00:00'))
                ->setQuoteNumber('DEV-2026-001')
                ->setExpirationDate(new \DateTime('2026-04-06'))
                ->setCustomer($contacts['jeanne']),
            'green' => $this->entity($manager, Quote::class, ['quoteNumber' => 'DEV-2026-002'])
                ->setCreatedAt(new \DateTimeImmutable('2026-03-16 10:30:00'))
                ->setQuoteNumber('DEV-2026-002')
                ->setExpirationDate(new \DateTime('2026-04-16'))
                ->setCustomer($contacts['malik']),
        ];

        foreach ($quotes as $quote) {
            $manager->persist($quote);
        }

        $quoteItem1 = $this->entity($manager, QuoteItem::class, ['quote' => $quotes['atelier'], 'productService' => $services['audit']])
            ->setQuote($quotes['atelier'])
            ->setProductService($services['audit']);
        $quoteItem2 = $this->entity($manager, QuoteItem::class, ['quote' => $quotes['atelier'], 'productService' => $services['montage']])
            ->setQuote($quotes['atelier'])
            ->setProductService($services['montage']);
        $quoteItem3 = $this->entity($manager, QuoteItem::class, ['quote' => $quotes['green'], 'productService' => $services['montage']])
            ->setQuote($quotes['green'])
            ->setProductService($services['montage']);

        $manager->persist($quoteItem1);
        $manager->persist($quoteItem2);
        $manager->persist($quoteItem3);

        $addOns = [
            $this->entity($manager, AddOnProduct::class, ['title' => 'Express review', 'quoteItem' => $quoteItem1])
                ->setTitle('Express review')
                ->setDescription('Relecture sous 48h des pieces complementaires.')
                ->setPrice(390.0)
                ->setPercentage(10.0)
                ->setQuoteItem($quoteItem1),
            $this->entity($manager, AddOnProduct::class, ['title' => 'Coaching oral', 'quoteItem' => $quoteItem2])
                ->setTitle('Coaching oral')
                ->setDescription('Preparation a la soutenance ou au comite de selection.')
                ->setPrice(650.0)
                ->setPercentage(15.0)
                ->setQuoteItem($quoteItem2),
            $this->entity($manager, AddOnProduct::class, ['title' => 'Traduction executive summary', 'quoteItem' => $quoteItem3])
                ->setTitle('Traduction executive summary')
                ->setDescription('Version anglaise de la synthese executive.')
                ->setPrice(280.0)
                ->setPercentage(7.0)
                ->setQuoteItem($quoteItem3),
        ];

        foreach ($addOns as $addOn) {
            $manager->persist($addOn);
        }

        $events = [
            $this->entity($manager, EventCustomer::class, ['slug' => 'webinaire-financement-innovation'])
                ->setTitle('Webinaire financement innovation')
                ->setSlug('webinaire-financement-innovation')
                ->setStartDate(new \DateTime('2026-04-22 11:00:00'))
                ->setEndDate(new \DateTime('2026-04-22 12:00:00'))
                ->setTheme('Financement')
                ->setUrl('https://example.test/events/webinaire-financement-innovation')
                ->setDescription('Presentation des aides mobilisables pour les PME innovantes.'),
            $this->entity($manager, EventCustomer::class, ['slug' => 'atelier-transition-industrielle'])
                ->setTitle('Atelier transition industrielle')
                ->setSlug('atelier-transition-industrielle')
                ->setStartDate(new \DateTime('2026-05-12 09:30:00'))
                ->setEndDate(new \DateTime('2026-05-12 11:30:00'))
                ->setTheme('Transition')
                ->setUrl('https://example.test/events/atelier-transition-industrielle')
                ->setDescription('Atelier pratique pour structurer un dossier de decarbonation.'),
        ];

        foreach ($events as $event) {
            $manager->persist($event);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param array<string, mixed> $criteria
     *
     * @return T
     */
    private function entity(ObjectManager $manager, string $className, array $criteria): object
    {
        return $manager->getRepository($className)->findOneBy($criteria) ?? new $className();
    }

    /**
     * @return list<User>
     */
    private function resolveFundingRequestOwners(ObjectManager $manager): array
    {
        $owners = [];

        foreach (self::FUNDING_REQUEST_OWNER_IDS as $id) {
            $user = $manager->getRepository(User::class)->find($id);

            if ($user instanceof User) {
                $owners[] = $user;
            }
        }

        if ([] === $owners) {
            throw new \RuntimeException(sprintf(
                'Aucun utilisateur funding_request.user n’a été trouvé pour les IDs autorisés : %s',
                implode(', ', self::FUNDING_REQUEST_OWNER_IDS)
            ));
        }

        return $owners;
    }
}
