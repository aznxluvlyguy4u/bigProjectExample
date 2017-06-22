<?php


namespace AppBundle\Validation;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\LoggerUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Find animals with themselves being their own ascendant.
 *
 * Class AscendantValidator
 * @package AppBundle\Validation
 */
class AscendantValidator
{
    /** @var ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Logger */
    private $logger;

    /** @var array */
    private $femalePedigreeSearchArray;
    /** @var array */
    private $malePedigreeSearchArray;
    /** @var array */
    private $ascendantsSearchArray;
    /** @var array */
    private $incorrectPedigrees;

    /**
     * AscendantValidator constructor.
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, CommandUtil $cmdUtil, Logger $logger = null)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->cmdUtil = $cmdUtil;
    }


    public function run()
    {
        $this->initializePrivateValues();
        $this->findDirectParents();
        $this->findAscendants();
        $this->persistIncorrectPedigrees();
    }


    private function initializePrivateValues()
    {
        $this->writeln('Initialize empty private arrays ...');
        $this->malePedigreeSearchArray = [];
        $this->femalePedigreeSearchArray = [];
        $this->ascendantsSearchArray = [];
        $this->incorrectPedigrees = [];
    }


    private function findDirectParents()
    {
        $this->writeln('Find direct parents ... ');
        $sql = "SELECT a.id as animal_id, a.parent_mother_id, a.parent_father_id, type
                FROM animal a
                WHERE type <> 'Neuter'
                ORDER BY date_of_birth ASC";
        $fullPedigreeData = $this->conn->query($sql)->fetchAll();

        $this->writeln('Creating pedigree search arrays ...');
        foreach ($fullPedigreeData as $pedigreeRecord)
        {
            $animalId = $pedigreeRecord['animal_id'];
            $fatherId = $pedigreeRecord['parent_father_id'];
            $motherId = $pedigreeRecord['parent_mother_id'];
            $type = $pedigreeRecord['type'];

            if($fatherId != null || $motherId != null) {
                if($type == AnimalObjectType::Ram) {
                    $this->malePedigreeSearchArray[$animalId] = $pedigreeRecord;
                } elseif($type == AnimalObjectType::Ewe) {
                    $this->femalePedigreeSearchArray[$animalId] = $pedigreeRecord;
                }
            }
        }
    }


    private function findAscendants()
    {
        $this->writeln('Find ascendants ... ');

        $totalCount = count($this->malePedigreeSearchArray) + count($this->femalePedigreeSearchArray);
        $counter = 0;
        $this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);
        foreach (['male' => $this->malePedigreeSearchArray, 'female' => $this->femalePedigreeSearchArray]
                as $type => $parentSearchArray) {
            foreach ($parentSearchArray as $animalId => $parents)
            {
                $this->ascendantsSearchArray = [];

                $ascendants = [];
                $ascendants[] = $animalId;
                $animalIdByTypeChain = [];
                $animalIdByTypeChain[] = [
                    'animal_id' => $animalId,
                    'type' => $type,
                ];

                $this->addParentsToAscendants($animalId, $parents, $ascendants, $animalIdByTypeChain);
                $message = 'Animals being their own ascendants found: ' . count($this->incorrectPedigrees);
                $this->cmdUtil->advanceProgressBar(1, $message);

                $counter++;

                //TODO REMOVE AFTER TESTING
                if($counter > 50000) {
                    $this->persistIncorrectPedigrees();
                    die;
                }
            }
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function addParentsToAscendants($primaryAnimalId, $parents, $ascendants = [], $animalIdByTypeChain = [])
    {
        if($parents === null || $primaryAnimalId === null) { return; }

        $fatherId = $parents['parent_father_id'];
        $motherId = $parents['parent_mother_id'];

        if($fatherId == null && $motherId == null) {
            return;
        }

        $animalIdByTypeChainFather = $animalIdByTypeChain;
        $animalIdByTypeChainMother = $animalIdByTypeChain;
        $ascendantsFather = $ascendants;
        $ascendantsMother = $ascendants;
        $breakOutOfFatherLoop = false;
        $breakOutOfMotherLoop = false;


        $group = ArrayUtil::get($primaryAnimalId, $this->ascendantsSearchArray, []);

        if($fatherId) {

            $animalIdByTypeChainFather[] = [
                'animal_id' => $fatherId,
                'type' => 'father',
            ];

            $ascendantsFather = $this->checkAscendants($fatherId, $ascendantsFather, $animalIdByTypeChainFather);
            if(!is_array($ascendantsFather)) { $breakOutOfFatherLoop = true; } //break out of infinite loop!

            $group[$fatherId] = $fatherId;
        }

        if($motherId) {

            $animalIdByTypeChainMother[] = [
                'animal_id' => $motherId,
                'type' => 'mother',
            ];

            $ascendantsMother = $this->checkAscendants($motherId, $ascendantsMother, $animalIdByTypeChainMother);
            if(!is_array($ascendantsMother)) { $breakOutOfMotherLoop = true; } //break out of infinite loop!

            $group[$motherId] = $motherId;
        }


        if($fatherId != null && $motherId != null) {
            if(key_exists($fatherId, $this->incorrectPedigrees) && key_exists($motherId, $this->incorrectPedigrees)) {
                return;
            }
        }


        $this->ascendantsSearchArray[$primaryAnimalId] = $group;

        if($fatherId && !$breakOutOfFatherLoop) {
            $parentsOfFather = ArrayUtil::get($fatherId, $this->malePedigreeSearchArray);
            if($parentsOfFather) {
                $this->addParentsToAscendants($primaryAnimalId, $parentsOfFather, $ascendantsFather, $animalIdByTypeChainFather);
            }
        }

        if($motherId && !$breakOutOfMotherLoop) {
            $parentsOfMother = ArrayUtil::get($motherId, $this->femalePedigreeSearchArray);
            if($parentsOfMother) {
                $this->addParentsToAscendants($primaryAnimalId, $parentsOfMother, $ascendantsMother, $animalIdByTypeChainMother);
            }
        }
    }


    /**
     * @param int $parentId
     * @param array $ascendants
     * @param array $animalIdByTypeChain
     * @return bool|array
     */
    private function checkAscendants($parentId, $ascendants, $animalIdByTypeChain)
    {
        if(key_exists($parentId, $this->incorrectPedigrees)) {
            return true;
        }

        if(key_exists($parentId, $ascendants)) {

            $chains = $this->parseChains($parentId, $animalIdByTypeChain);
            $this->incorrectPedigrees[$parentId] =
                [
                    'animal_id' => $parentId,
                    'parent_ids' => $chains['parent_ids'],
                    'parent_types' => $chains['parent_types'],
                ];
            return true;

        }

        $ascendants[$parentId] = $parentId;

        return $ascendants;
    }


    /**
     * @param $parentId
     * @param $animalIdByTypeChain
     * @return array
     */
    private function parseChains($parentId, $animalIdByTypeChain)
    {
        $animalIdChain = '';
        $parentTypes = '';
        $prefix = '';

        $foundStartOfChain = false;

        foreach ($animalIdByTypeChain as $values)
        {
            $animalId = $values['animal_id'];
            $type = $values['type'];

            if($animalId === $parentId) {
                $foundStartOfChain = true;
            }

            if($foundStartOfChain) {
                $animalIdChain = $animalIdChain . $prefix . $animalId;
                $parentTypes = $parentTypes . $prefix . $type;
                $prefix = ',';
            }
        }

        return [
            'parent_ids' => '['.$animalIdChain.']',
            'parent_types' => '['.$parentTypes.']',
        ];
    }


    private function persistIncorrectPedigrees()
    {
        //TODO
    }



    /**
     * @param $string
     */
    private function writeln($string)
    {
        if ($this->logger) {
            $this->logger->notice($string);
        } elseif ($this->cmdUtil) {
            $this->cmdUtil->writeln($string);
        } else {
            echo $string;
        }
    }


}