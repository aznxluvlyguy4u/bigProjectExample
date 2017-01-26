<?php

namespace AppBundle\Command;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
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
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

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

            default:
                $output->writeln('ABORTED');
                break;
        }
    }

}
