<?php


namespace AppBundle\Service\Worker;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\InternalWorkerResponse;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Worker\Logic\DeclareDepartAction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class DepartInternalWorkerCliOptions
 */
class DepartInternalWorkerCliOptions
{
    /** @var ObjectManager|EntityManagerInterface $em */
    private $em;

    private $txtFileOptions = array(
        'finder_in' => 'app/Resources/imports/internal_worker',
        'finder_name' => 'depart_queue_messages.json',
    );


    /**
     * DepartInternalWorkerCliOptions constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        //Print intro
        $cmdUtil->writelnClean(CommandUtil::generateTitle(CommandTitle::DEPART_INTERNAL_WORKER));

        $cmdUtil->writelnClean(DoctrineUtil::getDatabaseHostAndNameString($this->em));
        if(!$cmdUtil->generateConfirmationQuestion('Apply changes to this database? (y/n)')) {
            $cmdUtil->writeln('ABORTED');
            return;
        }

        $instructionText = "
    /*
     * The source file should contain a nested json of one or more DeclareDepart messages
     * as copy-pasted from the AWS-SQS queue
     *
     * {
     *  \"departs\":
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
     * The file should be named: ".$this->txtFileOptions['finder_name']."
     * and be placed in folder: ".$this->txtFileOptions['finder_in']."
     */
     ";
        $cmdUtil->writelnClean($instructionText);


        $isFlushAfterEachDepartAction = $cmdUtil->generateConfirmationQuestion('Flush after each individual message? (y/n)');

        $isSkipProcessedDeclares = true;
        $declareDepartAction = new DeclareDepartAction($this->em, $isSkipProcessedDeclares);

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
            $cmdUtil->writeln('Missing Declares: ');
            foreach ($missingDeclares as $declareRequestId) {
                $cmdUtil->write($declareRequestId.' ; ');
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