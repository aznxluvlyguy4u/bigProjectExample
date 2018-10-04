<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Component\Utils;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\BasicRvoDeclareInterface;
use AppBundle\Entity\DeclareAnimalDataInterface;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Location;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\RevokeDeclarationResponse;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\TagStateType;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

class RevokeProcessor extends DeclareProcessorBase implements RevokeProcessorInterface
{

    function revokeArrival(DeclareArrival $arrival): RevokeDeclaration
    {
        $animal = $this->getAnimalFromDeclare($arrival);
        $animal->setTransferredTransferState();
        $animal->setLocation(null);
        $animal->setIsDepartedAnimal(false);

        $this->removeLastOpenResidenceOnLocation($arrival->getLocation(), $animal);

        $this->clearLivestockCacheForLocationPrioritizedByActiveUbn($arrival->getUbnPreviousOwner());
        $this->getCacheService()->clearLivestockCacheForLocation($arrival->getLocation());

        $revoke = $this->createRevokeDeclaration($arrival);

        $arrival->setRevokedRequestState();
        $this->getManager()->persist($animal);
        $this->getManager()->persist($arrival);

        $this->getManager()->flush();

        return $revoke;
    }


    function revokeExport(DeclareExport $export): RevokeDeclaration
    {
        $location = $export->getLocation();

        $animal = $this->getAnimalFromDeclare($export);
        $animal->setTransferState(null);
        $animal->setLocation($location);
        $animal->setIsExportAnimal(false);
        $animal->setIsDepartedAnimal(false);

        $location->addAnimal($animal);

        $revoke = $this->createRevokeDeclaration($export);

        $export->setRevokedRequestState();
        $this->getManager()->persist($location);
        $this->getManager()->persist($animal);
        $this->getManager()->persist($export);

        $this->getManager()->flush();

        $this->getCacheService()->clearLivestockCacheForLocation($location);

        return $revoke;
    }


    function revokeDepart(DeclareDepart $depart): RevokeDeclaration
    {
        $location = $depart->getLocation();

        $animal = $this->getAnimalFromDeclare($depart);
        $animal->setTransferState(null);
        $currentLocation = $animal->getLocation();
        $animal->setLocation($location);
        $animal->setIsDepartedAnimal(false);

        $location->addAnimal($animal);

        $revoke = $this->createRevokeDeclaration($depart);

        $this->reopenClosedResidenceOnLocationByEndDate($location, $animal, $depart->getDepartDate());

        $depart->setRevokedRequestState();
        $this->getManager()->persist($location);
        $this->getManager()->persist($animal);
        $this->getManager()->persist($depart);

        $this->getManager()->flush();

        $this->getCacheService()->clearLivestockCacheForLocation($location);
        $this->getCacheService()->clearLivestockCacheForLocation($currentLocation);

        return $revoke;
    }


    function revokeImport(DeclareImport $import): RevokeDeclaration
    {
        $location = $import->getLocation();

        $animal = $this->getAnimalFromDeclare($import);
        $animal->setLocation(null);
        $animal->setIsExportAnimal(true);
        $location->removeAnimal($animal);

        $this->removeLastOpenResidenceOnLocation($location, $animal);

        $this->getManager()->persist($location);
        $this->getManager()->persist($animal);

        $isNewlyCreatedAnimal = false;
        if ($isNewlyCreatedAnimal) {
            $this->getManager()->flush();
            $this->getManager()->remove($animal);
        }

        $revoke = $this->createRevokeDeclaration($import);
        $import->setRevokedRequestState();

        $this->getManager()->persist($import);

        $this->getManager()->flush();
        $this->getCacheService()->clearLivestockCacheForLocation($location);

        return $revoke;
    }


    function revokeLoss(DeclareLoss $loss): RevokeDeclaration
    {
        $location = $loss->getLocation();

        $animal = $this->getAnimalFromDeclare($loss);
        $animal->setIsAlive(true);
        $animal->setDateOfDeath(null);

        $this->reopenClosedResidenceOnLocationByEndDate($location, $animal, $loss->getDateOfDeath());

        $revoke = $this->createRevokeDeclaration($loss);

        $loss->setRevokedRequestState();
        $this->getManager()->persist($animal);
        $this->getManager()->persist($loss);

        $this->getManager()->flush();
        $this->getCacheService()->clearLivestockCacheForLocation($location);

        return $revoke;
    }


    function revokeTagReplace(DeclareTagReplace $tagReplace): RevokeDeclaration
    {
        $location = $tagReplace->getLocation();

        $animal = $this->getAnimalFromDeclare($tagReplace);

        $replacedTag = $this->getManager()->getRepository(Tag::class)
            ->findReplacedTag($tagReplace->getUlnCountryCodeToReplace(), $tagReplace->getUlnNumberToReplace());
        if ($replacedTag) {
            $animal->removeUlnHistory($replacedTag);
            $this->getManager()->persist($animal);
            $this->getManager()->flush();

            $this->getManager()->remove($replacedTag);
        }

        $tagToRestore = new Tag();
        $tagToRestore->setUlnCountryCode($tagReplace->getUlnCountryCodeReplacement());
        $tagToRestore->setUlnNumber($tagReplace->getUlnNumberReplacement());
        $tagToRestore->setAnimalOrderNumber($tagReplace->getAnimalOrderNumberReplacement());
        $tagToRestore->setOrderDate(new \DateTime());
        $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
        $tagToRestore->setLocation($location);
        $tagToRestore->setOwner($location->getOwner());
        $this->getManager()->persist($tagToRestore);

        $animal->setUlnCountryCode($tagReplace->getUlnCountryCodeToReplace());
        $animal->setUlnNumber($tagReplace->getUlnNumberToReplace());
        $animal->setAnimalOrderNumber($tagReplace->getAnimalOrderNumberToReplace());

        $revoke = $this->createRevokeDeclaration($tagReplace);

        $tagReplace->setRevokedRequestState();
        $this->getManager()->persist($animal);
        $this->getManager()->persist($tagReplace);

        $this->getManager()->flush();
        $this->getCacheService()->clearLivestockCacheForLocation($location);

        return $revoke;
    }


    /**
     * @param BasicRvoDeclareInterface $declare
     * @return RevokeDeclaration
     */
    private function createRevokeDeclaration(BasicRvoDeclareInterface $declare): RevokeDeclaration
    {
        $revokeDeclaration = new RevokeDeclaration();
        $revokeDeclaration->setMessageId($declare->getMessageId());
        $revokeDeclaration->setRequestId($declare->getRequestId());

        $revokeDeclaration->setRequestTypeToRevoke(Utils::getClassName($declare));
        // For simplicity, it is assumed that the revoking client is similar to the declaring client
        $revokeDeclaration->setRelationNumberKeeper($declare->getRelationNumberKeeper());
        $revokeDeclaration->setUbn($declare->getUbn());
        $revokeDeclaration->setLocation($declare->getLocation());
        $declare->setRevoke($revokeDeclaration);
        $revokeDeclaration->setFinishedRequestState();

        $this->createRevokeSuccessResponse($revokeDeclaration);

        $this->getManager()->persist($revokeDeclaration);
        $this->getManager()->persist($declare);

        return $revokeDeclaration;
    }


    /**
     * @param RevokeDeclaration $revoke
     */
    private function createRevokeSuccessResponse(RevokeDeclaration $revoke)
    {
        $response = new RevokeDeclarationResponse();
        $response->setRevokeDeclarationIncludingAllValues($revoke);
        $response->setSuccessValues();
        $revoke->addResponse($response);

        $this->getManager()->persist($response);
        $this->getManager()->persist($revoke);
    }


    /**
     * @param DeclareAnimalDataInterface|DeclareTagReplace $declare
     * @return Animal
     */
    private function getAnimalFromDeclare($declare)
    {
        if ($declare->getAnimal()) {
            return $declare->getAnimal();
        }
        // First test, assuming animal is always linked to declare
        throw new PreconditionRequiredHttpException($this->translator->trans('NO ANIMAL WAS LINKED TO THIS DECLARE'));
    }


    /**
     * @param Location $location
     * @param Animal $animal
     */
    private function removeLastOpenResidenceOnLocation(Location $location, Animal $animal)
    {
        $residence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastOpenResidenceOnLocation($location, $animal);
        if ($residence) {
            $this->getManager()->remove($residence);
        }
    }


    /**
     * @param Location $location
     * @param Animal $animal
     * @param \DateTime $endDate
     */
    private function reopenClosedResidenceOnLocationByEndDate(Location $location, Animal $animal, \DateTime $endDate)
    {
        $residence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getByEndDate($location, $animal, $endDate);
        if ($residence) {
            $residence->setEndDate(null);
            $this->getManager()->persist($residence);
        }
    }


    private function clearLivestockCacheForLocationPrioritizedByActiveUbn($ubn)
    {
        $previousLocation = $this->getManager()->getRepository(Location::class)
            ->findOnePrioritizedByActiveUbn($ubn);
        if ($previousLocation) {
            $this->getCacheService()->clearLivestockCacheForLocation($previousLocation);
        }
    }

}