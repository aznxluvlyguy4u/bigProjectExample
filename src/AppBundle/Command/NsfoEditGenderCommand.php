<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoEditGenderCommand extends ContainerAwareCommand
{
    const TITLE = 'Edit gender of animal';
    const MALE = 'MALE';
    const FEMALE = 'FEMALE';

    /** @var OutputInterface */
    private $output;

    /** @var CommandUtil */
    private $cmdUtil;

    protected function configure()
    {
        $this
            ->setName('nsfo:edit:gender')
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

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        
        $id = $this->cmdUtil->generateQuestion('Insert id or uln of animal for which the gender needs to be changed', null);
        if($id === null) { $this->printNoAnimalFoundMessage($id); return;}

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);

        if(StringUtil::isStringContains($id, 'NL')) {
            $animal = $animalRepository->findAnimalByUlnString($id);
        } else {
            $animal = $animalRepository->find($id);
        }
        if(!($animal instanceof Animal)) { $this->printNoAnimalFoundMessage($id); return; }

        $this->printAnimalData($animal);

        $newGender = $this->askForNewGender();
        if($newGender == null) { $this->output->writeln('ABORTED'); return; }

        $genderChanger = new GenderChanger($em);
        if($genderChanger->hasDirectChildRelationshipCheck($animal)){
            if(!$this->cmdUtil->generateConfirmationQuestion('Animal has children. Changing gender will unset all children. ARE YOU SURE YOU WISH TO CONTINUE? (y/n)')){
                $this->output->writeln('ABORTED'); return;
            }
        }

        if(!$this->cmdUtil->generateConfirmationQuestion('Change gender from '.$animal->getGender().' to '.$newGender.'? (y/n)')){
            $this->output->writeln('ABORTED'); return;
        }


        if($newGender == self::MALE) {
            $newAnimal = $genderChanger->makeMale($animal);
        } elseif($newGender == self::FEMALE) {
            $newAnimal = $genderChanger->makeFemale($animal);
        } else {
            $this->output->writeln('ABORTED'); return;
        }
        $this->printAnimalData($newAnimal, '-- Data of Animal after gender change --');
    }


    /**
     * @return null|string
     */
    private function askForNewGender()
    {
        $newGenderInput = $this->cmdUtil->generateQuestion('Choose new gender, choose: MALE or FEMALE (exit = abort)', 0);

        if(strtoupper(substr($newGenderInput, 0, 1)) == 'M') {
            $newGender = self::MALE;
            $this->output->writeln('New gender choice: '.$newGender);
        } elseif (strtoupper(substr($newGenderInput, 0, 1)) == 'V' || strtoupper(substr($newGenderInput, 0, 1)) == 'F') {
            $newGender = self::FEMALE;
            $this->output->writeln('New gender choice: '.$newGender);
        } elseif (strtoupper($newGenderInput) == 'EXIT') {
            return null;
        } else {
            $this->output->writeln('NO GENDER INSERTED');
            $newGender = null;
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

    private function printAnimalData(Animal $animal, $header = '-- Following animal found --')
    {
        if($animal->getIsAlive() === true) {
            $isAliveString = 'true';
        } elseif($animal->getIsAlive() === false) {
            $isAliveString = 'false';
        } else {
            $isAliveString = 'null';
        }

        $this->output->writeln([  $header,
            'id: '.$animal->getId(),
            'uln: '.$animal->getUln(),
            'pedigree: '.$animal->getPedigreeCountryCode().$animal->getPedigreeNumber(),
            'aiind/vsmId: '.$animal->getName(),
            'gender: '.$animal->getGender(),
            'isAlive: '.$isAliveString,
            'dateOfBirth: '.$animal->getDateOfBirthString(),
            'dateOfDeath: '.$animal->getDateOfDeathString(),
            'current ubn: '.$animal->getUbn(),
        ]);
    }
}
