<?php

namespace AppBundle\Command;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Cache\ExteriorCacher;
use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Cache\NLingCacher;
use AppBundle\Cache\ProductionCacher;
use AppBundle\Cache\TailLengthCacher;
use AppBundle\Cache\WeightCacher;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\LitterUtil;
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
            '10: Update AnimalCache exterior values for all exteriors >= given logDate', "\n",
            '--- Location Focused ---', "\n",
            '11: Update AnimalCache of one Animal by animalId', "\n",
            '12: Generate new AnimalCache records for all animals, batched by location and ascendants', "\n",
            '--- Sql Batch Queries ---', "\n",
            '20: BatchUpdate all incongruent production values and n-ling values', "\n",
            '21: BatchUpdate all Incongruent exterior values', "\n",
            '22: BatchUpdate all Incongruent weight values', "\n",
            '23: BatchUpdate all Incongruent tailLength values', "\n\n",
            '--- Non AnimalCache Sql Batch Queries ---   ', "\n",
            '30: BatchUpdate heterosis and recombination values, non-updated only', "\n",
            '31: BatchUpdate heterosis and recombination values, regenerate all', "\n\n",
            '32: BatchUpdate match Mates and Litters, non-updated only', "\n",
            '33: BatchUpdate match Mates and Litters, regenerate all', "\n",
            '34: BatchUpdate remove Mates from REVOKED Litters', "\n",
            '35: BatchUpdate count Mates and Litters to be matched', "\n\n",
            '36: BatchUpdate suckleCount in Litters, update all incongruous values', "\n",
            '37: BatchUpdate remove suckleCount from REVOKED Litters', "\n\n",
            '38: BatchUpdate litterOrdinals in Litters, update all incongruous values', "\n",
            '39: BatchUpdate remove litterOrdinals from REVOKED Litters', "\n\n",
            '40: BatchUpdate gestationPeriods in Litters, update all incongruous values (incl. revoked litters and mates)', "\n",
            '41: BatchUpdate birthIntervals in Litters, update all incongruous values (incl. revoked litters and mates NOTE! Update litterOrdinals first!)', "\n\n",

            '', "\n",
            '--- Helper Commands ---', "\n",
            '99: Get locationId from UBN', "\n",

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
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update production and n-ling cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $output->writeln('Updating all records...');
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
                    $output->writeln('Updating all records...');
                    $updateCount = ExteriorCacher::updateAllExteriors($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = ExteriorCacher::updateExteriors($this->conn, [$animalId]);
                }
                $output->writeln([$updateCount.' animalCache records updated' ,'DONE!']);
                break;


            case 22:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update weight cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $output->writeln('Updating all records...');
                    $updateCount = WeightCacher::updateAllWeights($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = WeightCacher::updateWeights($this->conn, [$animalId]);
                }
                $output->writeln([$updateCount.' animalCache records updated' ,'DONE!']);
                break;

            case 23:
                $updateAll = $this->cmdUtil->generateConfirmationQuestion('Update tailLength cache values of all animals? (y/n, default = no)');
                if($updateAll) {
                    $output->writeln('Updating all records...');
                    $updateCount = TailLengthCacher::updateAll($this->conn);
                } else {
                    do{
                        $animalId = $this->cmdUtil->generateQuestion('Insert one animalId (default = 0)', 0);
                    } while (!ctype_digit($animalId) && !is_int($animalId));

                    $updateCount = TailLengthCacher::update($this->conn, [$animalId]);
                }
                $output->writeln([$updateCount.' animalCache records updated' ,'DONE!']);
                break;

            case 30: GeneDiversityUpdater::updateAll($this->conn, false, $this->cmdUtil); break;
            case 31: GeneDiversityUpdater::updateAll($this->conn, true, $this->cmdUtil); break;
            case 32: $output->writeln(LitterUtil::matchMatchingMates($this->conn, false).' \'mate-litter\'s matched'); break;
            case 33: $output->writeln(LitterUtil::matchMatchingMates($this->conn, true).' \'mate-litter\'s matched'); break;
            case 34: $output->writeln(LitterUtil::removeMatesFromRevokedLitters($this->conn).' \'mate-litter\'s unmatched'); break;
            case 35: $output->writeln(LitterUtil::countToBeMatchedLitters($this->conn).' \'mate-litter\'s to be matched'); break;
            case 36: $output->writeln(LitterUtil::updateSuckleCount($this->conn).' suckleCounts updated'); break;
            case 37: $output->writeln(LitterUtil::removeSuckleCountFromRevokedLitters($this->conn).' suckleCounts removed from revoked litters'); break;
            case 38: $output->writeln(LitterUtil::updateLitterOrdinals($this->conn).' litterOrdinals updated'); break;
            case 39: $output->writeln(LitterUtil::removeLitterOrdinalFromRevokedLitters($this->conn).' litterOrdinals removed from revoked litters'); break;
            case 40: $output->writeln(LitterUtil::updateGestationPeriods($this->conn).' gestationPeriods updated'); break;
            case 41: $output->writeln(LitterUtil::updateBirthInterVal($this->conn).' birthIntervals updated'); break;

            case 99:
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
