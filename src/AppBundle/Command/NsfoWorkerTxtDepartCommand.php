<?php

namespace AppBundle\Command;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartRepository;
use AppBundle\Entity\DeclareDepartResponse;
use AppBundle\Entity\DeclareDepartResponseRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\InternalWorkerResponse;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\SuccessIndicator;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\IRUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Worker\Logic\DeclareDepartAction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class NsfoWorkerTxtDepartCommand
 * @package AppBundle\Command
 */
class NsfoWorkerTxtDepartCommand extends ContainerAwareCommand
{
    const TITLE = 'DEPART Internal worker workaround using a txt file of the json messages';

    /** @var ObjectManager $em */
    private $em;

    private $txtFileOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_name' => 'NSFO_ProdErrorLog_2016-09-11_UBN304397.txt',
    );


    /*
     * The source file should contain a nested json of one or more DeclareDepart messages
     * as copy-pasted from the AWS-SQS queue
     *
     * {
     *  "departs":
     *     [
     *       {
     *         *depart message as copy pasted from queue*
     *       },
     *       {
     *         *depart message as copy pasted from queue*
     *       }
     *     ]
     * }
     *
     */


    protected function configure()
    {
        $this
            ->setName('nsfo:worker:txt:depart')
            ->setDescription(self::TITLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);


        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $output->writeln(DoctrineUtil::getDatabaseHostAndNameString($em));
        if(!$cmdUtil->generateConfirmationQuestion('Apply changes to this database? (y/n)')) {
            $output->writeln('ABORTED');
            return;
        }

        $isSkipProcessedDeclares = true;
        $declareDepartAction = new DeclareDepartAction($em, $isSkipProcessedDeclares);

        $jsonText = $this->getTxtFileContent();
        $content = new ArrayCollection(json_decode($jsonText, true));
        $departResponses = $content->first();

        $message = 'Processing DeclareDepart responses with internal worker logic';
        $cmdUtil->setStartTimeAndPrintIt(count($departResponses)+1, 1, $message);

        $successResponseCount = 0;
        $failedResponseCount = 0;
        $declareMissingCount = 0;
        $alreadyFinishedDeclares = 0;
        $missingDeclares = array();

        $isFlushAfterEachDepartAction = false;
        foreach ($departResponses as $departResponseArray) {

            $responseSuccessType = $declareDepartAction->save($departResponseArray, $isFlushAfterEachDepartAction);

            switch ($responseSuccessType) {
                case InternalWorkerResponse::SUCCESS_RESPONSE:
                    $successResponseCount++;
                    break;
                case InternalWorkerResponse::FAILED_RESPONSE:
                    $failedResponseCount++;
                    break;
                case InternalWorkerResponse::ALREADY_FINISHED:
                    $alreadyFinishedDeclares++;
                    break;
                case InternalWorkerResponse::MISSING_DECLARE:
                    $missingDeclares++;
                    $missingDeclares[] = $departResponseArray[JsonInputConstant::REQUEST_ID];
                    break;
                default:
                    $missingDeclares++;
                    $missingDeclares[] = $departResponseArray[JsonInputConstant::REQUEST_ID];
                    break;
            }

            $message = 'SuccessResponses: '.$successResponseCount.' | FailedResponses: '.$failedResponseCount.' | MissingDeclares: '.$declareMissingCount.' | AlreadyFinishedDeclares: '.$alreadyFinishedDeclares;
            $cmdUtil->advanceProgressBar(1, $message);
        }
        DoctrineUtil::flushClearAndGarbageCollect($this->em);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
        if(count($missingDeclares)>0) {
            $output->writeln('Missing Declares: ');
            foreach ($missingDeclares as $declareRequestId) {
                $output->write($declareRequestId.' ; ');
            }
        }
    }


    private function getTxtFileContent() {
        $finder = new Finder();
        $finder->files()
            ->in($this->txtFileOptions['finder_in'])
            ->name($this->txtFileOptions['finder_name'])
        ;
        foreach ($finder as $file) {
            return file_get_contents($file);
        }
    }

}
