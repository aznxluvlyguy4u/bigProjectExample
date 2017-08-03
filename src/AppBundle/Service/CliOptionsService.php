<?php


namespace AppBundle\Service;
use AppBundle\Cache\AnimalCacher;
use AppBundle\Cache\ExteriorCacher;
use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Cache\NLingCacher;
use AppBundle\Cache\ProductionCacher;
use AppBundle\Cache\TailLengthCacher;
use AppBundle\Cache\WeightCacher;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class CliOptionsService
 */
class CliOptionsService
{
    const DEFAULT_OPTION = 0;
    const DEFAULT_LOCATION_ID = 262;
    const DEFAULT_UBN = 1674459;

    const ANIMAL_CACHE_TITLE = 'UPDATE ANIMAL CACHE / RESULT TABLE VALUES';
    const LITTER_GENE_DIVERSITY_TITLE = 'UPDATE LITTER AND GENE DIVERSITY VALUES';
    
    /** @var ObjectManager */
    private $em;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;
    /** @var string */
    private $rootDir;

    /** @var AnimalRepository  */
    private $animalRepository;

    /**
     * CliOptionsService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     */
    public function __construct(ObjectManager $em, Logger $logger, $rootDir)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->rootDir = $rootDir;

        $this->conn = $em->getConnection();
        $this->animalRepository = $em->getRepository(Animal::class);
    }



    public function setCmdUtil(CommandUtil $cmdUtil)
    {
        if ($this->cmdUtil === null) { $this->cmdUtil = $cmdUtil; }
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function mainMenu(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $this->cmdUtil->printTitle('OVERVIEW OF ALL NSFO COMMANDS');

        $this->printDbInfo();

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '===============================================', "\n",
            '1: '.strtolower(self::ANIMAL_CACHE_TITLE), "\n",
            '2: '.strtolower(self::LITTER_GENE_DIVERSITY_TITLE), "\n",


            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->animalCacheOptions($this->cmdUtil); break;
            case 2: $this->litterAndGeneDiversityOptions($this->cmdUtil); break;
            default: return;
        }
        $this->mainMenu($this->cmdUtil);
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function animalCacheOptions(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $this->cmdUtil->printTitle(self::ANIMAL_CACHE_TITLE);

        $this->printDbInfo();

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '--- All & By Location ---', "\n",
            '1: Generate new AnimalCache records for all animals that do not have one yet', "\n",
            '2: Generate new AnimalCache records only for given locationId', "\n",
            '3: Regenerate all AnimalCache records for all animals', "\n",
            '4: Regenerate AnimalCache records only for given locationId', "\n",
            '5: Regenerate all AnimalCache records older than given stringDateTime (YYYY-MM-DD HH:MM:SS)', "\n",
            '6: Generate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '7: Regenerate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '8: Delete duplicate records', "\n",
            '9: Update location_of_birth_id for all animals and locations', "\n",
            '--- Location Focused ---', "\n",
            '11: Update AnimalCache of one Animal by animalId', "\n",
            '12: Generate new AnimalCache records for all animals, batched by location and ascendants', "\n",
            '--- Sql Batch Queries ---', "\n",
            '20: BatchUpdate all incongruent production values and n-ling values', "\n",
            '21: BatchUpdate all Incongruent exterior values', "\n",
            '22: BatchUpdate all Incongruent weight values', "\n",
            '23: BatchUpdate all Incongruent tailLength values', "\n\n",
            '', "\n",
            '--- Helper Commands ---', "\n",
            '99: Get locationId from UBN', "\n",

            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                AnimalCacher::cacheAnimalsBySqlInsert($this->em, $this->cmdUtil);
                $cmdUtil->writeln('DONE!');
                break;

            case 2:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsBySqlInsert($this->em, $this->cmdUtil, $locationId);
                $cmdUtil->writeln('DONE!');
                break;

            case 3:
                AnimalCacher::cacheAllAnimals($this->em, $this->cmdUtil, false);
                $cmdUtil->writeln('DONE!');
                break;

            case 4:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsOfLocationId($this->em, $locationId, $this->cmdUtil, false);
                $cmdUtil->writeln('DONE!');
                break;

            case 5:
                $todayDateString = TimeUtil::getTimeStampToday().' 00:00:00';
                $dateString = intval($this->cmdUtil->generateQuestion('insert dateTimeString (default = '.$todayDateString.')', $todayDateString));
                AnimalCacher::cacheAllAnimals($this->em, $this->cmdUtil, false, $dateString);
                $cmdUtil->writeln('DONE!');
                break;

            case 6:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsAndAscendantsByLocationId($this->em, true, null, $this->cmdUtil, $locationId);
                $cmdUtil->writeln('DONE!');
                break;

            case 7:
                $locationId = intval($this->cmdUtil->generateQuestion('insert locationId (default = '.self::DEFAULT_LOCATION_ID.')', self::DEFAULT_LOCATION_ID));
                AnimalCacher::cacheAnimalsAndAscendantsByLocationId($this->em, false, null, $this->cmdUtil, $locationId);
                $cmdUtil->writeln('DONE!');
                break;

            case 8:
                AnimalCacher::deleteDuplicateAnimalCacheRecords($this->em, $this->cmdUtil);
                $cmdUtil->writeln('DONE!');
                break;

            case 9:
                $this->animalRepository->updateAllLocationOfBirths($this->cmdUtil);
                $cmdUtil->writeln('DONE!');
                break;


            case 11:
                $this->cacheOneAnimalById();
                $cmdUtil->writeln('DONE!');
                break;

            case 12:
                AnimalCacher::cacheAllAnimalsByLocationGroupsIncludingAscendants($this->em, $this->cmdUtil);
                $cmdUtil->writeln('DONE!');
                break;


            case 20:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update production and n-ling cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $cmdUtil->writeln('Updating all records...');
                    $productionValuesUpdated = ProductionCacher::updateAllProductionValues($this->conn);
                    $nLingValuesUpdated = NLingCacher::updateAllNLingValues($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));
                    $productionValuesUpdated = ProductionCacher::updateProductionValues($this->conn, [$animalId]);
                    $nLingValuesUpdated = NLingCacher::updateNLingValues($this->conn, [$animalId]);
                }
                $this->cmdUtil->writeln($productionValuesUpdated.' production values updated');
                $this->cmdUtil->writeln($nLingValuesUpdated.' n-ling values updated');
                break;


            case 21:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update exterior cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $cmdUtil->writeln('Updating all records...');
                    $updateCount = ExteriorCacher::updateAllExteriors($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = ExteriorCacher::updateExteriors($this->conn, [$animalId]);
                }
                $cmdUtil->writeln([$updateCount.' animalCache records updated' ,'DONE!']);
                break;


            case 22:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update weight cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $cmdUtil->writeln('Updating all records...');
                    $updateCount = WeightCacher::updateAllWeights($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = WeightCacher::updateWeights($this->conn, [$animalId]);
                }
                $cmdUtil->writeln([$updateCount.' animalCache records updated' ,'DONE!']);
                break;

            case 23:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update tailLength cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $cmdUtil->writeln('Updating all records...');
                    $updateCount = TailLengthCacher::updateAll($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = TailLengthCacher::update($this->conn, [$animalId]);
                }
                $cmdUtil->writeln([$updateCount.' animalCache records updated' ,'DONE!']);
                break;

            case 99:
                $this->printLocationIdFromGivenUbn();
                $cmdUtil->writeln('DONE!');
                break;

            default: $this->writeLn('Exit menu'); return;
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


    /**
     * @param CommandUtil $cmdUtil
     */
    public function litterAndGeneDiversityOptions(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $this->cmdUtil->printTitle(self::LITTER_GENE_DIVERSITY_TITLE);

        $this->printDbInfo();

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '--- Non AnimalCache Sql Batch Queries ---   ', "\n",
            '1: BatchUpdate heterosis and recombination values, non-updated only', "\n",
            '2: BatchUpdate heterosis and recombination values, regenerate all', "\n\n",
            '3: BatchUpdate match Mates and Litters, non-updated only', "\n",
            '4: BatchUpdate match Mates and Litters, regenerate all', "\n",
            '5: BatchUpdate remove Mates from REVOKED Litters', "\n",
            '6: BatchUpdate count Mates and Litters to be matched', "\n\n",
            '7: BatchUpdate suckleCount in Litters, update all incongruous values', "\n",
            '8: BatchUpdate remove suckleCount from REVOKED Litters', "\n\n",
            '9: BatchUpdate litterOrdinals in Litters, update all incongruous values', "\n",
            '10: BatchUpdate remove litterOrdinals from REVOKED Litters', "\n\n",
            '11: BatchUpdate gestationPeriods in Litters, update all incongruous values (incl. revoked litters and mates)', "\n",
            '12: BatchUpdate birthIntervals in Litters, update all incongruous values (incl. revoked litters and mates NOTE! Update litterOrdinals first!)', "\n\n",

            'other: exit submenu', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1: GeneDiversityUpdater::updateAll($this->conn, false, $this->cmdUtil); break;
            case 2: GeneDiversityUpdater::updateAll($this->conn, true, $this->cmdUtil); break;
            case 3: $this->writeLn(LitterUtil::matchMatchingMates($this->conn, false).' \'mate-litter\'s matched'); break;
            case 4: $this->writeLn(LitterUtil::matchMatchingMates($this->conn, true).' \'mate-litter\'s matched'); break;
            case 5: $this->writeLn(LitterUtil::removeMatesFromRevokedLitters($this->conn).' \'mate-litter\'s unmatched'); break;
            case 6: $this->writeLn(LitterUtil::countToBeMatchedLitters($this->conn).' \'mate-litter\'s to be matched'); break;
            case 7: $this->writeLn(LitterUtil::updateSuckleCount($this->conn).' suckleCounts updated'); break;
            case 8: $this->writeLn(LitterUtil::removeSuckleCountFromRevokedLitters($this->conn).' suckleCounts removed from revoked litters'); break;
            case 9: $this->writeLn(LitterUtil::updateLitterOrdinals($this->conn).' litterOrdinals updated'); break;
            case 10: $this->writeLn(LitterUtil::removeLitterOrdinalFromRevokedLitters($this->conn).' litterOrdinals removed from revoked litters'); break;
            case 11: $this->writeLn(LitterUtil::updateGestationPeriods($this->conn).' gestationPeriods updated'); break;
            case 12: $this->writeLn(LitterUtil::updateBirthInterVal($this->conn).' birthIntervals updated'); break;

            default: $this->writeLn('Exit menu'); return;
        }
    }


    private function writeLn($line)
    {
        $this->cmdUtil->writelnWithTimestamp($line);
    }


    private function printDbInfo()
    {
        $this->cmdUtil->writeln(DoctrineUtil::getDatabaseHostAndNameString($this->em));
        $this->cmdUtil->writeln('');
    }
}