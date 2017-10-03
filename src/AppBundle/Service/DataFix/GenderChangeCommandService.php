<?php

namespace AppBundle\Service\DataFix;


use AppBundle\Command\NsfoMainCommand;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GenderChangeCommandService
 */
class GenderChangeCommandService extends DuplicateFixerBase
{

    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        //Print intro
        $cmdUtil->writelnClean(CommandUtil::generateTitle(NsfoMainCommand::GENDER_CHANGE));

        $cmdUtil->writelnClean([DoctrineUtil::getDatabaseHostAndNameString($this->em),'']);

        $developer = null;
        do {
            $lastName = null;
            $chooseDeveloperByLastName = $this->cmdUtil->generateConfirmationQuestion('Choose developer by lastName? (y/n, default = n = just use first developer in database)');
            if($chooseDeveloperByLastName) {
                DoctrineUtil::printDeveloperLastNamesInDatabase($this->conn, $cmdUtil);
                $lastName = $this->cmdUtil->generateQuestion('Insert lastName of developer', null);
                $lastName = strval($lastName);
            }
            $developer = DoctrineUtil::getDeveloper($this->em, $lastName);
        } while ($developer == null);
        $this->cmdUtil->writeln(['','Chosen developer: '.$developer->getLastName(),'']);

        $id = $this->cmdUtil->generateQuestion('Insert id or uln of animal for which the gender needs to be changed', null);
        if($id === null) { $this->printNoAnimalFoundMessage($id); return;}

        $animal = $this->findAnimalByIdOrUln($id);

        if(!($animal instanceof Animal)) {
            $this->printNoAnimalFoundMessage($id);

            return;
        }

        DoctrineUtil::printAnimalData($cmdUtil, $animal, '-- Data of Animal before gender change --');
        $newGender = $this->askForNewGender();

        if($newGender == null) {
            $this->cmdUtil->writeln(strtoupper(Constant::ABORTED_NAMESPACE));

            return;
        }

        $genderChanger = new GenderChanger($this->em);

        if($genderChanger->hasDirectChildRelationshipCheck($animal)){
            if(!$this->cmdUtil->generateConfirmationQuestion(
                'Animal has children. Changing gender wil alter history, which is currently not allowed, aborting.')){
                $this->cmdUtil->writeln(strtoupper(Constant::ABORTED_NAMESPACE)); return;
            }
        }

        if(!$this->cmdUtil->generateConfirmationQuestion('Change gender from '.$animal->getGender().' to '.$newGender.'? (y/n)')){
            $this->cmdUtil->writeln(strtoupper(Constant::ABORTED_NAMESPACE)); return;
        }

        switch ($newGender) {
            case AnimalObjectType::RAM:
                $result = $genderChanger->changeToGender($animal, Ram::class, $developer);
                break;
            case AnimalObjectType::EWE:
                $result = $genderChanger->changeToGender($animal, Ewe::class, $developer);
                break;
            case AnimalObjectType::NEUTER:
                $result = $genderChanger->changeToGender($animal, Neuter::class, $developer);
                break;
            default:
                $this->cmdUtil->writeln(strtoupper(Constant::ABORTED_NAMESPACE));
                return;
        }

        if (!$result instanceof JsonResponse) {
            $this->em->clear();
            $animal = $this->findAnimalByIdOrUln($id);
            DoctrineUtil::printAnimalData($cmdUtil, $animal, '-- Data of Animal after gender change --');
        } else { //Error has been occured, print message
            $this->cmdUtil->writeln($result);
        }
    }

    /**
     * @return null|string
     */
    private function askForNewGender()
    {
        $newGenderInput = $this->cmdUtil->generateQuestion('Choose new gender, choose: RAM (r), EWE (e), NEUTER (n). Quit (q)', 0);
        $newGender = null;

        if(strtolower(substr($newGenderInput, 0, 1)) == 'r') {
            $newGender = AnimalObjectType::RAM;
            $this->cmdUtil->writeln('New gender will be: '.$newGender);
        } elseif (strtolower(substr($newGenderInput, 0, 1)) == 'e') {
            $newGender = AnimalObjectType::EWE;
            $this->cmdUtil->writeln('New gender will be: '.$newGender);
        } elseif (strtolower(substr($newGenderInput, 0, 1)) == 'n') {
            $newGender = AnimalObjectType::NEUTER;
            $this->cmdUtil->writeln('New gender will be: '.$newGender);
        } elseif (strtolower($newGenderInput) == 'q') {
            return null;
        } else {
            $this->cmdUtil->writeln('NO GENDER given');
        }

        if($this->cmdUtil->generateConfirmationQuestion('Is this new gender correct? (y/n)')) {
            return $newGender;
        } else {
            return $this->askForNewGender();
        }
    }

    private function printNoAnimalFoundMessage($id)
    {
        $this->cmdUtil->writeln('no animal found for input: '.$id);
    }


    /**
     * @param string|int $id
     * @return Animal|Ewe|Neuter|Ram|null
     */
    private function findAnimalByIdOrUln($id)
    {
        if(StringUtil::isStringContains($id, 'NL')) {
            return $this->animalRepository->findAnimalByUlnString($id);
        } else {
            return $this->animalRepository->find($id);
        }
    }
}