<?php

namespace AppBundle\Command;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;

class NsfoGenderChangeCommand extends ContainerAwareCommand
{
    const TITLE = 'Edit gender of animal';
    const taskAbortedNamespace = 'ABORTED';

    /** @var OutputInterface */
    private $output;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var ObjectManager */
    private $em;

    /** @var Connection */
    private $conn;

    protected function configure()
    {
        $this
            ->setName('nsfo:gender:change')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->output = $output;
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $output->writeln([DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $developer = null;
        do {
            $lastName = null;
            $chooseDeveloperByLastName = $this->cmdUtil->generateConfirmationQuestion('Choose developer by lastName? (y/n, default = n = just use first developer in database)');
            if($chooseDeveloperByLastName) {
                DoctrineUtil::printDeveloperLastNamesInDatabase($this->conn, $output);
                $lastName = $this->cmdUtil->generateQuestion('Insert lastName of developer', null);
                $lastName = strval($lastName);
            }
            $developer = DoctrineUtil::getDeveloper($em, $lastName);
        } while ($developer == null);
        $this->cmdUtil->writeln(['','Chosen developer: '.$developer->getLastName(),'']);

        $id = $this->cmdUtil->generateQuestion('Insert id or uln of animal for which the gender needs to be changed', null);
        if($id === null) { $this->printNoAnimalFoundMessage($id); return;}

        $animal = $this->findAnimalByIdOrUln($id);

        if(!($animal instanceof Animal)) {
            $this->printNoAnimalFoundMessage($id);

            return;
        }

        DoctrineUtil::printAnimalData($output, $animal, '-- Data of Animal before gender change --');
        $newGender = $this->askForNewGender();

        if($newGender == null) {
            $this->output->writeln(self::taskAbortedNamespace);

            return;
        }

        $genderChanger = new GenderChanger($em);

        if($genderChanger->hasDirectChildRelationshipCheck($animal)){
            if(!$this->cmdUtil->generateConfirmationQuestion(
              'Animal has children. Changing gender wil alter history, which is currently not allowed, aborting.')){
                $this->output->writeln(self::taskAbortedNamespace); return;
            }
        }

        if(!$this->cmdUtil->generateConfirmationQuestion('Change gender from '.$animal->getGender().' to '.$newGender.'? (y/n)')){
            $this->output->writeln(self::taskAbortedNamespace); return;
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
                $this->output->writeln(self::taskAbortedNamespace);
                return;
        }

        if (!$result instanceof JsonResponse) {
            $em->clear();
            $animal = $this->findAnimalByIdOrUln($id);
            DoctrineUtil::printAnimalData($output, $animal, '-- Data of Animal after gender change --');
        } else { //Error has been occured, print message
            $this->output->writeln($result);
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
            $this->output->writeln('New gender will be: '.$newGender);
        } elseif (strtolower(substr($newGenderInput, 0, 1)) == 'e') {
            $newGender = AnimalObjectType::EWE;
            $this->output->writeln('New gender will be: '.$newGender);
        } elseif (strtolower(substr($newGenderInput, 0, 1)) == 'n') {
            $newGender = AnimalObjectType::NEUTER;
            $this->output->writeln('New gender will be: '.$newGender);
        } elseif (strtolower($newGenderInput) == 'q') {
            return null;
        } else {
            $this->output->writeln('NO GENDER given');
        }

        if($this->cmdUtil->generateConfirmationQuestion('Is this new gender correct? (y/n)')) {
            return $newGender;
        } else {
            return $this->askForNewGender();
        }
    }

    private function printNoAnimalFoundMessage($id)
    {
        $this->output->writeln('no animal found for input: '.$id);
    }


    /**
     * @param string|int $id
     * @return Animal|Ewe|Neuter|Ram|null
     */
    private function findAnimalByIdOrUln($id)
    {
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->em->getRepository(Animal::class);

        if(StringUtil::isStringContains($id, 'NL')) {
            return $animalRepository->findAnimalByUlnString($id);
        } else {
            return $animalRepository->find($id);
        }
    }
}
