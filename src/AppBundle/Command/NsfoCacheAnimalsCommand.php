<?php

namespace AppBundle\Command;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoCacheAnimalsCommand extends ContainerAwareCommand
{
    const TITLE = 'Generate cache for animals';
    const DEFAULT_OPTION = 0;
    const DEFAULT_LOCATION_ID = 262;
    const DEFAULT_UBN = 1674459;

    /** @var ObjectManager $em */
    private $em;

    /** @var Connection $conn */
    private $conn;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var AnimalRepository */
    private $animalRepository;

    protected function configure()
    {
        $this
            ->setName('nsfo:cache:animals')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->conn = $em->getConnection();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->animalRepository = $em->getRepository(Animal::class);

        $this->cmdUtil->printTitle('AnimalCache / ResultTable');

        $this->cmdUtil->writeln([DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate new AnimalCache records for all animals that do not have one yet', "\n",
            '2: Generate new AnimalCache records only for given locationId', "\n",
            '3: Regenerate all AnimalCache records for all animals', "\n",
            '4: Regenerate AnimalCache records only for given locationId', "\n",
            '5: Regenerate all AnimalCache records older than given stringDateTime (YYYY-MM-DD HH:MM:SS)', "\n",
            '6: Generate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '7: Regenerate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '8: Delete duplicate records', "\n",
            '9: Update location_of_birth_id for all animals and locations', "\n",
            '10: Update AnimalCache exterior values for all exteriors >= given logDate', "\n",
            '--------------------------------------------------------------------------', "\n",
            '11: Update AnimalCache of one Animal by animalId', "\n",
            '12: Generate new AnimalCache records for all animals, batched by location and ascendants', "\n",
            '--------------------------------------------------------------------------', "\n",
            '20: Get locationId from UBN', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                AnimalCacher::cacheAnimalsBySqlInsert($em, $this->cmdUtil);
                $output->writeln('DONE!');
                break;

            case 2:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsBySqlInsert($em, $this->cmdUtil, $locationId);
                $output->writeln('DONE!');
                break;

            case 3:
                AnimalCacher::cacheAllAnimals($em, $this->cmdUtil, false);
                $output->writeln('DONE!');
                break;

            case 4:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsOfLocationId($em, $locationId, $this->cmdUtil, false);
                $output->writeln('DONE!');
                break;

            case 5:
                $todayDateString = TimeUtil::getTimeStampToday().' 00:00:00';
                $dateString = intval($this->cmdUtil->generateQuestion('insert dateTimeString (default = '.$todayDateString.')', $todayDateString));
                AnimalCacher::cacheAllAnimals($em, $this->cmdUtil, false, $dateString);
                $output->writeln('DONE!');
                break;

            case 6:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsAndAscendantsByLocationId($em, true, null, $this->cmdUtil, $locationId);
                $output->writeln('DONE!');
                break;

            case 7:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsAndAscendantsByLocationId($em, false, null, $this->cmdUtil, $locationId);
                $output->writeln('DONE!');
                break;

            case 8:
                AnimalCacher::deleteDuplicateAnimalCacheRecords($em, $this->cmdUtil);
                $output->writeln('DONE!');
                break;

            case 9:
                $this->animalRepository->updateAllLocationOfBirths($this->cmdUtil);
                $output->writeln('DONE!');
                break;

            case 10:
                AnimalCacher::cacheExteriorsEqualOrOlderThanLogDate($em, null, $this->cmdUtil);
                $output->writeln('DONE!');
                break;


            case 11:
                $this->cacheOneAnimalById();
                $output->writeln('DONE!');
                break;

            case 12:
                AnimalCacher::cacheAllAnimalsByLocationGroupsIncludingAscendants($em, $this->cmdUtil);
                $output->writeln('DONE!');
                break;
            

            case 20:
                $this->printLocationIdFromGivenUbn();
                $output->writeln('DONE!');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }
    
    
    private function printLocationIdFromGivenUbn()
    {
        do {
            $ubn = $this->cmdUtil->generateQuestion('Insert UBN (default = '.self::DEFAULT_UBN.')', self::DEFAULT_UBN);
        } while (!ctype_digit($ubn) && !is_int($ubn));

        $result = $this->conn->query("SELECT id, is_active FROM location WHERE ubn = '".$ubn."' ORDER BY is_active DESC LIMIT 1")->fetch();
        

        if($result) {
            $isActiveText = ArrayUtil::get('is_active', $result) ? 'ACTIVE' : 'NOT ACTIVE';
            $this->cmdUtil->writeln('locationId: ' . ArrayUtil::get('id', $result) .' ('. $isActiveText.')');
        } else {
            $this->cmdUtil->writeln('NO LOCATION');
        }

    }


    private function cacheOneAnimalById()
    {
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->em->getRepository(Animal::class);

        do {
            $animal = null;
            $animalId = $this->cmdUtil->generateQuestion('Insert animalId', null);

            if(ctype_digit($animalId) || is_int($animalId)) {
                /** @var Animal $animal */
                $animal = $animalRepository->find($animalId);

                if($animal == null) { $this->cmdUtil->writeln('No animal found for given id: '.$animalId); }
            } else {
                $this->cmdUtil->writeln('AnimalId '.$animalId.' is incorrect. It must be an integer.');
            }

        } while ($animal == null);

        AnimalCacher::cacheByAnimal($this->em, $animal);
    }

}
