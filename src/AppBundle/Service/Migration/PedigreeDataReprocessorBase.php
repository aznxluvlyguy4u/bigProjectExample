<?php


namespace AppBundle\Service\Migration;


use AppBundle\Entity\Animal;
use AppBundle\Service\PedigreeDataGenerator;
use AppBundle\Util\CommandUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

class PedigreeDataReprocessorBase
{
    const DEFAULT_START_ANIMAL_ID = 1;

    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;
    /** @var PedigreeDataGenerator */
    private $pedigreeDataGenerator;

    public function __construct(EntityManagerInterface $em,
                                Logger $logger,
                                PedigreeDataGenerator $pedigreeDataGenerator)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->pedigreeDataGenerator = $pedigreeDataGenerator;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return PedigreeDataGenerator
     */
    public function getPedigreeDataGenerator()
    {
        return $this->pedigreeDataGenerator;
    }


    /**
     * @param boolean $onlyIncludeCurrentLivestockAnimals
     * @param int $minAnimalId
     * @return array|Animal[]
     */
    public function getAllAnimalsFromDeclareBirth($onlyIncludeCurrentLivestockAnimals, $minAnimalId)
    {
        $this->logger->notice('Retrieving declareBirth animals'
            .($onlyIncludeCurrentLivestockAnimals ? ', only including current livestock' : '')
            .' ...');
        $animals = $this->getManager()->getRepository(Animal::class)
            ->getAllAnimalsFromDeclareBirth($onlyIncludeCurrentLivestockAnimals, $minAnimalId)
        ;
        $this->logger->notice(count($animals).' animals retrieved');
        return $animals;
    }


    /**
     * @param CommandUtil $commandUtil
     * @return string
     */
    protected function askForStartAnimalId(CommandUtil $commandUtil)
    {
        return $commandUtil->generateQuestion('insert animalId to start from (default = '.self::DEFAULT_START_ANIMAL_ID.')', self::DEFAULT_START_ANIMAL_ID);
    }
}