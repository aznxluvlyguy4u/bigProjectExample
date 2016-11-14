<?php


namespace AppBundle\Migration;


use AppBundle\Component\MessageBuilderBase;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\ActionType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class TagReplaceMigrator extends MigratorBase
{
    const BATCH = 1000;

    /** @var Employee */
    private $developer;

    /**
     * RacesMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     * @param Employee $developer
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data, Employee $developer = null)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data);
        $this->developer = $developer;
    }
    
    public function migrate()
    {
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);
        $animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmId();

        $sql = "SELECT uln_country_code_replacement, uln_country_code_to_replace, uln_number_to_replace, uln_number_replacement FROM declare_tag_replace";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $ulnReplacementsInDatabase = [];
        foreach ($results as $result) {
            $uln = $result['uln_country_code_replacement'].' '.$result['uln_number_replacement'];
            $searchArray[$uln] = $uln;
        }

        $newCount = 0;
        foreach ($this->data as $record) {

            $ulnNew = $record[3];

            //Check if record was already saved in the database
            if(!array_key_exists($ulnNew, $ulnReplacementsInDatabase)) {

                $vsmId = $record[0];
                $dateTimeString = $record[1];
                $dateTime = $dateTimeString == null || $dateTimeString == '' ? null : new \DateTime($dateTimeString);
                $ulnOld = $record[2];


                $oldUlnParts = explode(' ', $ulnOld);

                $oldUlnCountryCode = null;
                $oldUlnNumber = null;
                switch (count($oldUlnParts)) {
                    case 1:
                        $oldUlnCountryCode = null;
                        $oldUlnNumber = $oldUlnParts[0];
                        break;

                    case 2:
                        $oldUlnCountryCode = $oldUlnParts[0];
                        $oldUlnNumber = $oldUlnParts[1];
                        break;

                    case 3:
                        $oldUlnCountryCode = $oldUlnParts[0];
                        $oldUlnNumber = $oldUlnParts[1].' '.$oldUlnParts[2];
                        break;
                }

                $newUlnParts = explode(' ', $ulnNew);
                $newUlnCountryCode = $newUlnParts[0];
                $newUlnNumber = $newUlnParts[1];

                if($newUlnCountryCode != null && $newUlnNumber != null && $oldUlnNumber != null) {
                    $declareTagTransfer = new DeclareTagReplace();
                    $declareTagTransfer->setUlnCountryCodeToReplace($oldUlnCountryCode);
                    $declareTagTransfer->setUlnNumberToReplace($oldUlnNumber);
                    $declareTagTransfer->setUlnCountryCodeReplacement($newUlnCountryCode);
                    $declareTagTransfer->setUlnNumberReplacement($newUlnNumber);

                    $declareTagTransfer->setReplaceDate($dateTime);
                    $declareTagTransfer->setLogDate(new \DateTime());
                    $declareTagTransfer->setRequestState(RequestStateType::IMPORTED);
                    $requestId = MessageBuilderBase::getNewRequestId();
                    $declareTagTransfer->setRequestId($requestId);
                    $declareTagTransfer->setMessageId($requestId);
                    $declareTagTransfer->setAction(ActionType::V_MUTATE);
                    $declareTagTransfer->setRecoveryIndicator(RecoveryIndicatorType::N);
                    //TODO

                    if($animalIdsByVsmId->containsKey($vsmId)) {
                        $animal = $this->animalRepository->find($animalIdsByVsmId->get($vsmId));
                        $declareTagTransfer->setAnimal($animal);
                    }
                    $this->em->persist($declareTagTransfer);
                    $newCount++;
                }

                $this->cmdUtil->advanceProgressBar(1,'Records to be persisted: '.$newCount);
                if($newCount%self::BATCH == 0) { $this->em->flush(); }
            }
        }
        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}