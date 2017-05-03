<?php

namespace AppBundle\Command;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Cache\ExteriorCacher;
use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Cache\NLingCacher;
use AppBundle\Cache\ProductionCacher;
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

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var Connection */
    private $conn;

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

        $output->writeln(['',DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate new AnimalCache records for all animals that do not have one yet', "\n",
            '2: Generate new AnimalCache records only for given locationId', "\n",
            '3: Regenerate all AnimalCache records for all animals', "\n",
            '4: Regenerate AnimalCache records only for given locationId', "\n",
            '5: Regenerate all AnimalCache records older than given stringDateTime (YYYY-MM-DD HH:MM:SS)', "\n",
            '6: Generate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '7: Regenerate all AnimalCache records for animal and ascendants (3gen) for given locationId', "\n",
            '8: Delete duplicate records', "\n\n",
            '--- Sql Batch Queries ---', "\n",
            '20: BatchUpdate all incongruent production values and n-ling values', "\n",
            '21: BatchUpdate all Incongruent exterior values', "\n",
            '22: BatchUpdate all Incongruent weight values', "\n\n",
            '--- Non AnimalCache Sql Batch Queries ---   ', "\n",
            '30: BatchUpdate heterosis and recombination values, non-updated only', "\n",
            '31: BatchUpdate heterosis and recombination values, regenerate all', "\n\n",
            '32: BatchUpdate match Mates and Litters, non-updated only', "\n",
            '33: BatchUpdate match Mates and Litters, regenerate all', "\n",
            '34: BatchUpdate remove Mates from REVOKED Litters', "\n",
            '35: BatchUpdate count Mates and Litters to be matched', "\n\n",
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

            case 30: GeneDiversityUpdater::update($this->conn, [], false, $this->cmdUtil); break;
            case 31: GeneDiversityUpdater::update($this->conn, [], true, $this->cmdUtil); break;
            case 32: $output->writeln(LitterUtil::matchMatchingMates($this->conn, false).' \'mate-litter\'s matched'); break;
            case 33: $output->writeln(LitterUtil::matchMatchingMates($this->conn, true).' \'mate-litter\'s matched'); break;
            case 34: $output->writeln(LitterUtil::removeMatesFromRevokedLitters($this->conn).' \'mate-litter\'s unmatched'); break;
            case 35: $output->writeln(LitterUtil::countToBeMatchedLitters($this->conn).' \'mate-litter\'s to be matched'); break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }

}
