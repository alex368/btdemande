<?php

namespace App\Service;

use App\Entity\Campany;
use Doctrine\ORM\EntityManagerInterface;

class CampanyCreatorService
{
    public function __construct(
        private EntityManagerInterface $em,
        private InseeApiService $inseeApiService
    ) {}



    

    public function createFromArray(array $data): Campany
    {
        $campany = new Campany();

        $campany->setLegalName($data['legalName']);
        $campany->setSector($data['sector'] ?? 'Non renseigné');
        $campany->setAdress($data['adress'] ?? 'Non renseignée');
        $campany->setSiren($data['siren']);
        $campany->setStage($data['stage'] ?? 'Création');
        $campany->setProjetName($data['projetName'] ?? null);

        if (!empty($data['creationDate'])) {
            $campany->setCreationDate(new \DateTime($data['creationDate']));
        } else {
            $campany->setCreationDate(new \DateTime());
        }

        $this->em->persist($campany);
        $this->em->flush();

        return $campany;
    }

 public function createFromSiret(string $siret): ?Campany
{
    $companyData = $this->inseeApiService->fetchCompanyBySiret($siret);

    if (!$companyData || !isset($companyData['etablissement'])) {
        return null;
    }

    $etablissement = $companyData['etablissement'];
    $uniteLegale = $etablissement['uniteLegale'];

    $campany = new Campany();
    $campany->setLegalName($uniteLegale['denominationUniteLegale'] ?? 'Non renseigné');
    $campany->setSector($uniteLegale['activitePrincipaleUniteLegale'] ?? 'Non renseigné');
    $campany->setAdress(
        ($etablissement['adresseEtablissement']['numeroVoieEtablissement'] ?? '') . ' ' .
        ($etablissement['adresseEtablissement']['typeVoieEtablissement'] ?? '') . ' ' .
        ($etablissement['adresseEtablissement']['libelleVoieEtablissement'] ?? '') . ' ' .
        ($etablissement['adresseEtablissement']['codePostalEtablissement'] ?? '') . ' ' .
        ($etablissement['adresseEtablissement']['libelleCommuneEtablissement'] ?? '')
    );
    // SIREN pris depuis l'établissement
    $campany->setSiren($etablissement['siren']);
    $campany->setCreationDate(new \DateTime($uniteLegale['dateCreationUniteLegale']));
    $campany->setStage('Créée');

    $this->em->persist($campany);
    $this->em->flush();

    return $campany;
}



    public function createFromSiren(string $siren): ?Campany
{
    // Récupération via l'API INSEE par SIREN
    $companyData = $this->inseeApiService->fetchCompanyBySiren($siren);

    if (!$companyData || !isset($companyData['uniteLegale'])) {
        return null;
    }

    $uniteLegale = $companyData['uniteLegale'];

    // Vérifier que le SIREN est présent
    if (empty($uniteLegale['siren'])) {
        return null; // impossible de créer sans SIREN
    }

    $campany = new Campany();
    $campany->setLegalName($uniteLegale['denominationUniteLegale'] ?? 'Non renseigné');
    $campany->setSector($uniteLegale['activitePrincipaleUniteLegale'] ?? 'Non renseigné');

    // Adresse
    $adresse = $uniteLegale['adresseEtablissement'] ?? [];
    $campany->setAdress(
        trim(
            ($adresse['numeroVoieEtablissement'] ?? '') . ' ' .
            ($adresse['typeVoieEtablissement'] ?? '') . ' ' .
            ($adresse['libelleVoieEtablissement'] ?? '')
        )
    );

    $campany->setSiren($uniteLegale['siren']);
    $campany->setCreationDate(
        new \DateTime($uniteLegale['dateCreationUniteLegale'] ?? date('Y-m-d'))
    );
    $campany->setStage('Créée');

    $this->em->persist($campany);
    $this->em->flush();

    return $campany;
}




    

    
}