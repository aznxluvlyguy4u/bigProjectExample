<?php


namespace AppBundle\Service;


use AppBundle\Constant\BreedIndexTypeConstant;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedIndexType;
use AppBundle\Entity\BreedIndexTypeRepository;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\BreedValueTypeRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

class BreedIndexService
{

    /** @var Connection */
    private $conn;
    /** @var ObjectManager */
    private $em;
    /** @var Logger */
    private $logger;

    /** @var BreedIndexTypeRepository */
    private $breedIndexTypeRepository;
    /** @var BreedValueTypeRepository */
    private $breedValueTypeRepository;

    /**
     * MixBlupOutputFilesService constructor.
     * @param ObjectManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, $logger = null)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;

        $this->breedIndexTypeRepository = $this->em->getRepository(BreedIndexType::class);
        $this->breedValueTypeRepository = $this->em->getRepository(BreedValueType::class);
    }

    /**
     * @return bool
     */
    public function initializeBreedIndexType()
    {
        $breedIndexTypeEntities = $this->breedIndexTypeRepository->findAll();
        $searchArray = [];

        /** @var BreedIndexType $breedIndexTypeEntity */
        foreach ($breedIndexTypeEntities as $breedIndexTypeEntity) {
            $searchArray[$breedIndexTypeEntity->getEn()] = $breedIndexTypeEntity->getNl();
        }

        $breedIndexTypeValues = BreedIndexTypeConstant::getConstants();
        $newCount = 0;
        foreach ($breedIndexTypeValues as $english => $dutch) {
            if(!key_exists($english, $searchArray)) {
                $breedIndexType = new BreedIndexType($english, $dutch);
                $this->em->persist($breedIndexType);
                $newCount++;
            }
        }

        if($newCount > 0) {
            $this->em->flush();
            $this->logger->notice($newCount . ' new BreedIndexType records persisted');
        } else {
            $this->logger->notice('No new BreedIndexType records persisted');
        }

        return $newCount > 0;
    }


    /**
     * @return bool
     */
    public function initializeBreedValueType()
    {
        $breedValueTypeEntities = $this->breedValueTypeRepository->findAll();
        $searchArray = [];

        /** @var BreedValueType $breedValueTypeEntity */
        foreach ($breedValueTypeEntities as $breedValueTypeEntity) {
            $searchArray[$breedValueTypeEntity->getEn()] = $breedValueTypeEntity->getNl();
        }

        $breedValueTypeValues = BreedValueTypeConstant::getConstants();
        $newCount = 0;
        foreach ($breedValueTypeValues as $english => $dutch) {
            if(!key_exists($english, $searchArray)) {
                $breedValueType = new BreedValueType($english, $dutch);
                $this->em->persist($breedValueType);
                $newCount++;
            }
        }
        if($newCount > 0) {
            $this->em->flush();
            $this->logger->notice($newCount . ' new BreedValueType records persisted');
        } else {
            $this->logger->notice('No new BreedValueType records persisted');
        }

        return $newCount > 0;
    }
}