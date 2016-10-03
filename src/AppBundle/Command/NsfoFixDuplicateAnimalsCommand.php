<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\BreedCodes;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\GenderType;
use AppBundle\Migration\BreedCodeReformatter;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\GenderChanger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFixDuplicateAnimalsCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix Duplicate Animals: Due to Animal Import after AnimalSync around July-Aug 2016';
    const ID_LIMIT = 611040;
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
    private $animalsGroupedByUln;
    
    protected function configure()
    {
        $this
            ->setName('nsfo:fix:duplicate:animals')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->output = $output;
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        /** @var BreedCodeReformatter $breedCodeReformatter */
        $this->breedCodeReformatter = new BreedCodeReformatter($this->em, false, new ArrayCollection());

        /** @var GenderChanger genderChanger */
        $this->genderChanger = new GenderChanger($this->em);

        /** @var AnimalRepository $animalRepository */
        $this->animalRepository = $this->em->getRepository(Animal::class);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $this->fixDuplicateAnimals();
    }




    /**
     * @param boolean $isGetAnimalEntities
     * @return ArrayCollection
     */
    private function findDuplicateAnimals($isGetAnimalEntities = true)
    {
        $sql = "SELECT a.id, CONCAT(a.uln_country_code, a.uln_number) as uln FROM animal a
            INNER JOIN (
                SELECT uln_country_code, uln_number FROM animal
                GROUP BY uln_country_code, uln_number HAVING COUNT(*) > 1
                ) d ON d.uln_number = a.uln_number AND d.uln_country_code = a.uln_country_code";
        $ulnResults = $this->em->getConnection()->query($sql)->fetchAll();

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
    private function fixDuplicateAnimals()
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
             * Keep the synced animals, identified by the lower id, because they contain the relationships with declares
             * Fix the gender where possible, read from the imported animal data
             * And copy the missing data from the imported animal to the synced animal
             */

            //TODO ONLY PASS animal ID and find/set the animals here
            /* 1. Identify primary animal */
            if($animal1->getId() > self::ID_LIMIT) {
                $primaryAnimal = $animal1;
                $secondaryAnimal = $animal2;
            } elseif($animal2->getId() > self::ID_LIMIT) {
                $primaryAnimal = $animal2;
                $secondaryAnimal = $animal1;
            } elseif($animal1->getId() < $animal2->getId()) {
                $primaryAnimal = $animal1;
                $secondaryAnimal = $animal2;
            } else {
                $primaryAnimal = $animal2;
                $secondaryAnimal = $animal1;
            }

            /* 2. Fix Gender if possible */
            $genderPrimaryAnimal = $primaryAnimal->getGender();
            $genderSecondaryAnimal = $secondaryAnimal->getGender();
            if($genderPrimaryAnimal != $genderSecondaryAnimal && $genderSecondaryAnimal != GenderType::NEUTER) {
                /* Note that Neuters are not set as parents anymore

                You can check it with this sql

                SELECT * FROM animal a
                LEFT JOIN animal f ON a.parent_father_id = f.id
                LEFT JOIN animal m ON a.parent_mother_id = m.id
                WHERE f.gender = 'NEUTER' OR m.gender = 'NEUTER';
                
                */

                if($genderSecondaryAnimal == GenderType::FEMALE) { $primaryAnimal = $this->genderChanger->makeFemale($primaryAnimal); }
                elseif($genderSecondaryAnimal == GenderType::MALE) { $primaryAnimal = $this->genderChanger->makeMale($primaryAnimal); }
            }

            /* 3. */
            $this->mergeValues($primaryAnimal, $secondaryAnimal);

            /* 4 Remove unnecessary duplicate */
            /** @var Animal $secondaryAnimal */
            $this->removeAnimal($secondaryAnimal);

            $this->em->persist($primaryAnimal);
            $this->em->flush();

            $this->cmdUtil->advanceProgressBar(1, 'Fixed animal: '.$primaryAnimal->getUln());
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
        $breedCodesId = $this->em->getConnection()->query($sql)->fetch()['id'];
        if($breedCodesId != null) {
            $sql = "DELETE FROM breed_code WHERE breed_codes_id = ".$breedCodesId;
            $this->em->getConnection()->exec($sql);
        }

        $sql = "UPDATE animal SET breed_codes_id = NULL WHERE id = ".$animal->getId();
        $this->em->getConnection()->exec($sql);

        $sql = "DELETE FROM breed_codes WHERE animal_id = ".$animal->getId();
        $this->em->getConnection()->exec($sql);

        /** @var Animal $animal */
        $animal = $this->em->merge($animal);
        $this->em->remove($animal);
    }


    /**
     * @param Animal $primaryAnimal
     * @param Animal $secondaryAnimal
     */
    private function mergeValues($primaryAnimal, $secondaryAnimal)
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
            /** @var Weight $weight */
            foreach ($secondaryAnimal->getDepartures() as $departure)
            {
                $departure->setAnimal($primaryAnimal);
            }
        }

        if($secondaryAnimal->getDeaths()->count() > 0) {
            /** @var Weight $weight */
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
