<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\BreedCodes;
use AppBundle\Entity\BreedValuesSet;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\Weight;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\GenderChanger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class DuplicateAnimalsFixerLegacy
{
    const OLD_DATE = '2016-09-22 11';

    /** @var ObjectManager $em */
    private $em;

    /** @var AnimalRepository $animalRepository */
    private $animalRepository;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var GenderChanger */
    private $genderChanger;

    /** @var BreedCodeReformatter */
    private $breedCodeReformatter;
    
    /** @var ArrayCollection */
    private $animalsGroupedByUln ;
    
    /** @var Connection */
    private $conn;


    public function __construct(ObjectManager $em, OutputInterface $output, CommandUtil $cmdUtil)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->output = $output;
        $this->cmdUtil = $cmdUtil;
        
        /** @var BreedCodeReformatter $breedCodeReformatter */
        $this->breedCodeReformatter = new BreedCodeReformatter($this->em, false, new ArrayCollection());

        /** @var GenderChanger genderChanger */
        $this->genderChanger = new GenderChanger($this->em);

        /** @var AnimalRepository $animalRepository */
        $this->animalRepository = $this->em->getRepository(Animal::class);
    }

    /**
     * @param boolean $isGetAnimalEntities
     * @return ArrayCollection
     */
    private function findDuplicateAnimals($isGetAnimalEntities = true)
    {
        //Note! Only if uln and dateOfBirth are identical, will it be seen as a duplicate animal
        $sql = "SELECT z.location_id, l.ubn, l.location_holder, z.id, CONCAT(z.uln_country_code, z.uln_number) as uln,
                      z.uln_number, z.uln_country_code,
                      z.name, z.gender, z.is_alive, z.date_of_birth, z.date_of_death, z.transfer_state 
                FROM animal z
                    INNER JOIN (
                        SELECT
                          a.id,
                          CONCAT(a.uln_country_code, a.uln_number) AS uln
                        FROM animal a
                          INNER JOIN (
                                       SELECT
                                         uln_country_code,
                                         uln_number
                                       FROM animal
                                       GROUP BY uln_country_code, uln_number, date_of_birth
                                       HAVING COUNT(*) > 1
                                     ) d ON d.uln_number = a.uln_number AND d.uln_country_code = a.uln_country_code
                        ORDER BY (a.uln_number, a.uln_country_code) ASC, a.name ISNULL, a.name DESC
                        ) y ON y.id = z.id
                    LEFT JOIN location l ON z.location_id = l.id";
        $ulnResults = $this->conn->query($sql)->fetchAll();

        $this->animalsGroupedByUln = new ArrayCollection();
        foreach ($ulnResults as $ulnResult)
        {
            $uln = $ulnResult['uln'];
            $animalId = $ulnResult['id'];

            $animalIds = $this->animalsGroupedByUln->get($uln);

            if($animalIds == null) {
                $animalIds = array();
            }

            if($isGetAnimalEntities) {
                $animalIds[] = $this->animalRepository->find($animalId);
            } else {
                $animalIds[] = $animalId;
            }

            $this->animalsGroupedByUln->set($uln, $animalIds);
        }
    }


    /**
     *
     */
    public function fixDuplicateAnimals()
    {
        $isGetAnimalEntities = true;
        $this->findDuplicateAnimals($isGetAnimalEntities);

        $startUnit = 1;
        $totalNumberOfUnits = $this->animalsGroupedByUln->count() + $startUnit;
        $startMessage = 'Fixing Duplicate Animals';

        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfUnits, $startUnit, $startMessage);

        foreach ($this->animalsGroupedByUln as $animalsGroup)
        {
            /** @var Animal $animal1 */
            $animal1 = $animalsGroup[0];
            /** @var Animal $animal2 */
            $animal2 = $animalsGroup[1];

            /* Assuming there are only 2 animals per duplicate pair, as checked in the database by sql-query,
             * Keep the imported animals and transfer the data from the synced animals
             * At this moment (2016nov2) the synced animals have the latest declare data
             * (depart & loss) and some measurements (fat, muscle, weight, breed_values_set)
             */

            if($animal1->getName() != null || $animal2->getName() != null) {

                /* 1. Identify primary animal */
                if($animal1->getName() != null) {
                    $primaryAnimal = $animal1;
                    $secondaryAnimal = $animal2;
                } else {
                    $primaryAnimal = $animal2;
                    $secondaryAnimal = $animal1;
                }

                //No gender fix necessary, because the imported animal is the primaryAnimal

                /* 2. */
                $this->mergeValuesWithImportedAnimalAsPrimaryAnimal($primaryAnimal, $secondaryAnimal);

                /* 3 Remove unnecessary duplicate */
                /** @var Animal $secondaryAnimal */
                $this->removeAnimal($secondaryAnimal); //TODO VERIFY THIS FUNCTION!

                $this->em->persist($primaryAnimal);
                $this->em->flush();

                $this->cmdUtil->advanceProgressBar(1, 'Fixed animal: '.$primaryAnimal->getUln());
            } else {
                $this->cmdUtil->advanceProgressBar(1, 'No imported animal found for: '.$animal1->getUln());
            }
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param Animal $animal
     */
    private function removeAnimal($animal)
    {
        /** @var Animal $animal */
        $animal = $this->em->merge($animal);
        /** @var BreedCodes $breedCodes */
        $breedCodes = $animal->getBreedCodes();
        if($breedCodes != null) {
            foreach ($breedCodes->getCodes() as $code)
            {
                $this->em->remove($code);
            }
            $breedCodes->removeAllCodes();
            $breedCodes->setAnimal(null);
            $animal->setBreedCodes(null);
            $this->em->remove($breedCodes);
        }
        $sql = "SELECT id FROM breed_codes WHERE animal_id = ".$animal->getId();
        $breedCodesId = $this->conn->query($sql)->fetch()['id'];
        if($breedCodesId != null) {
            $sql = "DELETE FROM breed_code WHERE breed_codes_id = ".$breedCodesId;
            $this->conn->exec($sql);
        }

        $sql = "UPDATE animal SET breed_codes_id = NULL WHERE id = ".$animal->getId();
        $this->conn->exec($sql);

        $sql = "DELETE FROM breed_codes WHERE animal_id = ".$animal->getId();
        $this->conn->exec($sql);

        $sql = "DELETE FROM breed_codes WHERE animal_id = ".$animal->getId();
        $this->conn->exec($sql);

        /** @var Animal $animal */
        $animal = $this->em->merge($animal);
        $this->em->remove($animal);
        $this->em->flush();
    }

    /**
     * @param Animal $primaryAnimal
     * @param Animal $secondaryAnimal
     */
    private function mergeValuesWithImportedAnimalAsPrimaryAnimal($primaryAnimal, $secondaryAnimal)
    {
        //NOTE!!! At this point (2016nov2) only the following values need to be merged
        //DeclareDepart logic values and animalId in fat, muscle, weight

        $tablesToUpdateOnlyAnimalId = ['body_fat', 'muscle_thickness', 'weight'];
        foreach($tablesToUpdateOnlyAnimalId as $table) {
            $sql = "UPDATE ".$table." SET animal_id = ".$primaryAnimal->getId()." WHERE animal_id = ".$secondaryAnimal->getId();
            $this->conn->exec($sql);
        }

        //Fix alive status
        if($secondaryAnimal->getIsAlive() != $primaryAnimal->getIsAlive()) {
            $bool = $secondaryAnimal->getIsAlive() ? 'true' : 'false';
            $sql = "UPDATE animal SET is_alive = ".$bool." WHERE id = ".$primaryAnimal->getId();
            $this->conn->exec($sql);
        }




        //DeclareDepart

        //Set TransferState
        if($secondaryAnimal->getTransferState() != null) {
            $sql = "UPDATE animal SET transfer_state = '".$secondaryAnimal->getTransferState()."' WHERE id = ".$primaryAnimal->getId();
            $this->conn->exec($sql);
        }

        //Set Location
        $currentLocation = $secondaryAnimal->getLocation();
        if($currentLocation != $primaryAnimal->getLocation()) {
            if($currentLocation == null) {
                $locationId = null;
            } else {
                $locationId = $currentLocation->getId();
            }

            $sql = "UPDATE animal SET location_id = '".$locationId."' WHERE id = ".$primaryAnimal->getId();
            $this->conn->exec($sql);
        }

        //Set TransferState
        if($secondaryAnimal->getIsExportAnimal() != $primaryAnimal->getIsExportAnimal()) {
            $bool = $secondaryAnimal->getIsExportAnimal() ? 'true' : 'false';
            $sql = "UPDATE animal SET is_export_animal = '".$bool."' WHERE id = ".$primaryAnimal->getId();
            $this->conn->exec($sql);
        }

        if($secondaryAnimal->getDepartures()->count() > 0) {
            /** @var DeclareDepart $departure */
            foreach ($secondaryAnimal->getDepartures() as $departure)
            {
                $sql = "UPDATE declare_depart SET animal_id = ".$primaryAnimal->getId()." WHERE id = ".$departure->getId();
                $this->conn->exec($sql);

            }

            //Depart Logic for Animal

            //Currently (2016nov2) for the imported animals, most have no animalResidence and some have one animalResidence with only a startDate
            //And the synced animals have only one animalResidence


            //Find last animalResidence of syncedAnimal
            $sql = "SELECT * FROM animal_residence WHERE animal_id = ".$secondaryAnimal->getId()."
                    ORDER BY start_date DESC LIMIT 1";
            $lastAnimalResidenceSyncedAnimal = $this->conn->query($sql)->fetch();


            //Find last animalResidence of importedAnimal
            $sql = "SELECT * FROM animal_residence WHERE animal_id = ".$primaryAnimal->getId()."
                    ORDER BY start_date DESC LIMIT 1";
            $lastAnimalResidenceImportedAnimal = $this->conn->query($sql)->fetch();


            if($lastAnimalResidenceSyncedAnimal) { //the synced residence exists | All synced duplicate animals with a declareDepart also have an animalResidence

                if($lastAnimalResidenceImportedAnimal) { //the imported residence exists, delete it
                    $sql = "DELETE FROM animal_residence WHERE id = ".$lastAnimalResidenceImportedAnimal['id'];
                    $this->conn->exec($sql);
                }

                $sql = "UPDATE animal_residence SET animal_id = ".$primaryAnimal->getId()." WHERE animal_id = ".$secondaryAnimal->getId();
                $this->conn->exec($sql);
            }
        }

        /** @var BreedValuesSet $breedValuesSetImportedAnimal */
        $breedValuesSetImportedAnimal = $this->em->getRepository(BreedValuesSet::class)->findOneBy(['animal' => $primaryAnimal]);
        /** @var BreedValuesSet $breedValuesSetSyncedAnimal */
        $breedValuesSetSyncedAnimal = $this->em->getRepository(BreedValuesSet::class)->findOneBy(['animal' => $secondaryAnimal]);

        if($breedValuesSetImportedAnimal == null && $breedValuesSetSyncedAnimal != null) {
            $breedValuesSetSyncedAnimal->setAnimal($primaryAnimal);
            $this->em->flush();
        } else if($breedValuesSetImportedAnimal != null && $breedValuesSetSyncedAnimal == null) {
            //Do nothing
        } else if($breedValuesSetImportedAnimal != null && $breedValuesSetSyncedAnimal != null) {
            if($breedValuesSetImportedAnimal->getLambMeatIndexAccuracy() == 0) {
                $breedValuesSetSyncedAnimal->setAnimal($primaryAnimal);
                $this->em->remove($breedValuesSetImportedAnimal);
                $this->em->flush();
            } else {
                $this->em->remove($breedValuesSetSyncedAnimal);
                $this->em->flush();
            }
        }


        //Finally remove all animalResidences of the to be deleted synced Animal
        $sql = "DELETE FROM animal_residence WHERE animal_id = ".$secondaryAnimal->getId();
        $this->conn->exec($sql);
    }


    /**
     * @param Animal $primaryAnimal
     * @param Animal $secondaryAnimal
     */
    private function mergeValuesWithSyncedAnimalAsPrimaryAnimal($primaryAnimal, $secondaryAnimal)
    {
        if($primaryAnimal->getPedigreeCountryCode() == null) {
            $primaryAnimal->setPedigreeCountryCode($secondaryAnimal->getPedigreeCountryCode());
        }

        if($primaryAnimal->getPedigreeNumber() == null) {
            $primaryAnimal->setPedigreeNumber($secondaryAnimal->getPedigreeNumber());
        }

        if($primaryAnimal->getDateOfDeath() == null) {
            $primaryAnimal->setDateOfDeath($secondaryAnimal->getDateOfDeath());
        }

        if($primaryAnimal->getName() == null) {
            $primaryAnimal->setName($secondaryAnimal->getName());
        }

        if($primaryAnimal->getTransferState() == null) {
            $primaryAnimal->setTransferState($secondaryAnimal->getTransferState());
        }

        if($primaryAnimal->getBreedType() == null) {
            $primaryAnimal->setBreedType($secondaryAnimal->getBreedType());
        }

        if($primaryAnimal->getBreedCode() == null) {
            $primaryAnimal->setBreedCode($secondaryAnimal->getBreedCode());
        }

        if($primaryAnimal->getScrapieGenotype() == null) {
            $primaryAnimal->setScrapieGenotype($secondaryAnimal->getScrapieGenotype());
        }

        if($primaryAnimal->getUbnOfBirth() == null) {
            $primaryAnimal->setUbnOfBirth($secondaryAnimal->getUbnOfBirth());
        }

        if($primaryAnimal->getPedigreeRegister() == null && $secondaryAnimal->getPedigreeRegister() != null) {
            $primaryAnimal->setPedigreeRegister($secondaryAnimal->getPedigreeRegister());
        }

        if($primaryAnimal->getMixblupBlock() == 2 || $primaryAnimal->getMixblupBlock() == null) {
            $primaryAnimal->setMixblupBlock($secondaryAnimal->getMixblupBlock());
        }

        /* Generate breed codes */
        if($primaryAnimal->getBreedCodes() == null) {
            $animalArray = new ArrayCollection();
            $animalArray->add($primaryAnimal);
            $this->breedCodeReformatter->setAnimals($animalArray);
            $this->breedCodeReformatter->migrate();
        }

        if($secondaryAnimal->getWeightMeasurements()->count() > 0) {
            /** @var Weight $weight */
            foreach ($secondaryAnimal->getWeightMeasurements() as $weight)
            {
                $weight->setAnimal($primaryAnimal);
            }
        }

        if($secondaryAnimal->getDepartures()->count() > 0) {
            /** @var DeclareDepart $departure */
            foreach ($secondaryAnimal->getDepartures() as $departure)
            {
                $departure->setAnimal($primaryAnimal);
            }
        }

        if($secondaryAnimal->getDeaths()->count() > 0) {
            /** @var DeclareLoss $loss */
            foreach ($secondaryAnimal->getDeaths() as $loss)
            {
                $loss->setAnimal($primaryAnimal);
            }
        }


        if($primaryAnimal->getAnimalResidenceHistory()->count() == 1 && $secondaryAnimal->getAnimalResidenceHistory()->count() > 0) {
            /** @var AnimalResidence $primaryAnimalResidence */
            $primaryAnimalResidence = $primaryAnimal->getAnimalResidenceHistory()->first();
            if($primaryAnimalResidence->getLogDate()->format('Y-m-d_H') == self::OLD_DATE
                && $primaryAnimalResidence->getStartDate()->format('Y-m-d_H') == self::OLD_DATE
                && $primaryAnimalResidence->getEndDate() == null
            ) {
                $this->em->remove($primaryAnimalResidence);
            }

            /** @var AnimalResidence $animalResidence */
            foreach ($secondaryAnimal->getAnimalResidenceHistory() as $animalResidence) {
                $animalResidence->setAnimal($primaryAnimal);
            }

            $primaryAnimal->setAnimalResidenceHistory($secondaryAnimal->getAnimalResidenceHistory());
        }
    }
    
    
}