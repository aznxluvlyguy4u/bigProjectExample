<?php

namespace AppBundle\Command;

use AppBundle\Entity\TagSyncErrorLog;
use AppBundle\Entity\TagSyncErrorLogRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\ErrorLogUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoErrorLogCommand extends ContainerAwareCommand
{
    const TITLE = 'Error log commands';
    const DEFAULT_OPTION = 0;

    /** @var CommandUtil */
    private $cmdUtil;
    /** @var OutputInterface */
    private $output;
    /** @var ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;

    /** @var TagSyncErrorLogRepository */
    private $tagSyncErrorLogRepository;

    protected function configure()
    {
        $this
            ->setName('nsfo:log:error')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->conn = $this->em->getConnection();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;
        $this->tagSyncErrorLogRepository = $this->em->getRepository(TagSyncErrorLog::class);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln(DoctrineUtil::getDatabaseHostAndNameString($this->em));

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Update TagSyncErrorLog records isFixed status', "\n",
            '2: List animalSyncs with tags blocked by existing animals', "\n",
            '3: Get sql filter query by RetrieveAnimalId', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $updateCount = ErrorLogUtil::updateTagSyncErrorLogIsFixedStatuses($this->conn);
                $this->cmdUtil->writeln($updateCount. ' TagSyncErrorLog statuses updated');
                $output->writeln('Done!');
                break;

            case 2:
                $this->cmdUtil->writeln(['retrieveAnimalsId' => 'blockingAnimalsCount']);
                $this->cmdUtil->writeln($this->tagSyncErrorLogRepository->listRetrieveAnimalIds());
                $output->writeln('Done!');
                break;

            case 3:
                $retrieveAnimalsId = $this->requestRetrieveAnimalsId();
                $this->cmdUtil->writeln($this->tagSyncErrorLogRepository->getQueryFilterByRetrieveAnimalIds($retrieveAnimalsId));
                $output->writeln('Done!');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }


    /**
     * @return string
     */
    private function requestRetrieveAnimalsId()
    {
        $listRetrieveAnimalsId = $this->tagSyncErrorLogRepository->listRetrieveAnimalIds();
        do {
            $this->cmdUtil->writeln('Valid RetrieveAnimalsIds by blockingAnimalsCount:');
            $this->cmdUtil->writeln($listRetrieveAnimalsId);
            $this->cmdUtil->writeln('-------------');
            $retrieveAnimalsId = $this->cmdUtil->generateQuestion('Insert RetrieveAnimalsId', 0);

            $isInvalidRetrieveAnimalsId = !key_exists($retrieveAnimalsId, $listRetrieveAnimalsId);
            if($isInvalidRetrieveAnimalsId) {
                $this->cmdUtil->writeln('Inserted RetrieveAnimalsId is invalid!');
            }

        } while($isInvalidRetrieveAnimalsId);
        return $retrieveAnimalsId;
    }
}
