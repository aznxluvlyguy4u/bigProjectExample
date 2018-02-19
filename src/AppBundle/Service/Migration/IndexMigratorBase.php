<?php


namespace AppBundle\Service\Migration;


use AppBundle\Constant\BreedIndexTypeConstant;
use AppBundle\Entity\BreedIndexCoefficient;
use AppBundle\Entity\BreedIndexCoefficientRepository;
use AppBundle\Entity\BreedIndexType;
use AppBundle\Entity\BreedValueType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

class IndexMigratorBase
{

    /** @var EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;
    /** @var BreedIndexCoefficientRepository */
    private $breedIndexCoefficientRepository;


    /**
     * LambMeatIndexMigrator constructor.
     * @param EntityManagerInterface|ObjectManager $em
     * @param Logger $logger
     */
    public function __construct(EntityManagerInterface $em, Logger $logger)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        /** @var BreedIndexCoefficientRepository breedIndexCoefficientRepository */
        $this->breedIndexCoefficientRepository = $em->getRepository(BreedIndexCoefficient::class);
    }

    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return BreedIndexCoefficientRepository
     */
    public function getBreedIndexCoefficientRepository()
    {
        return $this->breedIndexCoefficientRepository;
    }


    /**
     * @param BreedIndexCoefficient $retrievedBreedIndexCoefficient
     * @param BreedIndexCoefficient $referenceBreedIndexCoefficient
     * @return bool
     */
    protected function updateValues(BreedIndexCoefficient $retrievedBreedIndexCoefficient, BreedIndexCoefficient$referenceBreedIndexCoefficient)
    {
        if(
            $retrievedBreedIndexCoefficient->getC() !== $referenceBreedIndexCoefficient->getC()
            || $retrievedBreedIndexCoefficient->getVar() !== $referenceBreedIndexCoefficient->getVar()
            || $retrievedBreedIndexCoefficient->getT() !== $referenceBreedIndexCoefficient->getT()
        ) {
            $retrievedBreedIndexCoefficient->setC($referenceBreedIndexCoefficient->getC());
            $retrievedBreedIndexCoefficient->setVar($referenceBreedIndexCoefficient->getVar());
            $retrievedBreedIndexCoefficient->setT($referenceBreedIndexCoefficient->getT());
            $this->getManager()->persist($retrievedBreedIndexCoefficient);
            return true;
        }
        return false;
    }


    /**
     * @param $breedValueTypeConstant
     * @return BreedValueType
     */
    protected function getBreedValueType($breedValueTypeConstant)
    {
        return $this->getManager()->getRepository(BreedValueType::class)->findOneBy(['nl'=>$breedValueTypeConstant]);
    }


    /**
     * @param string $breedIndexTypeConstant
     * @return BreedIndexType
     */
    private function getBreedIndexType($breedIndexTypeConstant)
    {
        return $this->getManager()->getRepository(BreedIndexType::class)->findOneBy(['nl'=>$breedIndexTypeConstant]);
    }


    /**
     * @return BreedIndexType
     */
    protected function getLambMeatIndexType()
    {
        return $this->getBreedIndexType(BreedIndexTypeConstant::LAMB_MEAT_INDEX);
    }


    /**
     * @return BreedIndexType
     */
    protected function getExteriorIndexType()
    {
        return $this->getBreedIndexType(BreedIndexTypeConstant::EXTERIOR_INDEX);
    }


    /**
     * @return BreedIndexType
     */
    protected function getFertilityIndexType()
    {
        return $this->getBreedIndexType(BreedIndexTypeConstant::FERTILITY_INDEX);
    }


    /**
     * @return BreedIndexType
     */
    protected function getWormResistanceIndexType()
    {
        return $this->getBreedIndexType(BreedIndexTypeConstant::WORM_RESISTANCE_INDEX);
    }


    /**
     * @param BreedIndexType $breedIndexType
     * @return array
     */
    protected function getBreedIndexCoefficients($breedIndexType)
    {
        return $this->getBreedIndexCoefficientRepository()->findBy(['breedIndexType' => $breedIndexType]);
    }
}