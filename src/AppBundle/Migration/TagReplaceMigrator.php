<?php


namespace AppBundle\Migration;


use AppBundle\Component\MessageBuilderBase;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\ActionType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class TagReplaceMigrator extends MigratorBase
{
    const BATCH = 1000;

    /** @var Employee */
    private $developer;

    /** @var array */
    private $declareTagReplaceIdByOldUlns;

    /** @var array */
    private $oldUlnsByNewUlns;

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
        //TODO
        dump('TODO');die;

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
                        $oldUlnCountryCode = 'NL';
                        $oldUlnNumber = StringUtil::padUlnNumberWithZeroes($oldUlnParts[0]);
                        break;

                    case 2:
                        $oldUlnCountryCode = $oldUlnParts[0];
                        $oldUlnNumber = StringUtil::padUlnNumberWithZeroes($oldUlnParts[1]);
                        break;

                    case 3:
                        $oldUlnCountryCode = $oldUlnParts[0];
                        $oldUlnNumber =  StringUtil::padUlnNumberWithZeroes($oldUlnParts[2]);
                        break;
                }

                $newUlnParts = explode(' ', $ulnNew);
                $newUlnCountryCode = $newUlnParts[0];
                $newUlnNumber = StringUtil::padUlnNumberWithZeroes($newUlnParts[1]);

                if($newUlnCountryCode != null && $newUlnNumber != null && $oldUlnNumber != null && $oldUlnCountryCode != null) {
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


    /**
     * @return bool
     */
    public function setAnimalIdsOnDeclareTagReplaces()
    {
        //Create SearchArrays
        $sql = "SELECT id, uln_country_code_to_replace, uln_number_to_replace, uln_country_code_replacement, uln_number_replacement FROM declare_tag_replace
                WHERE animal_id ISNULL ";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        
        if(count($results) == 0) { return false; }

        $this->declareTagReplaceIdByOldUlns = [];
        $this->oldUlnsByNewUlns = [];
        foreach ($results as $result) {
            $oldUln = $result['uln_country_code_to_replace'].$result['uln_number_to_replace'];
            $declareTagReplaceId = $result['id'];
            $this->declareTagReplaceIdByOldUlns[$oldUln] = $declareTagReplaceId;

            $newUln = $result['uln_country_code_replacement'].$result['uln_number_replacement'];
            $this->oldUlnsByNewUlns[$newUln] = $oldUln;
        }


        //Set AnimalIds
        $sql = "SELECT a.id as animal_id, a.uln_country_code, a.uln_number, t.id, t.uln_country_code_to_replace, t.uln_number_to_replace FROM animal a
                INNER JOIN declare_tag_replace t ON a.uln_country_code = t.uln_country_code_replacement AND a.uln_number = t.uln_number_replacement
                WHERE animal_id ISNULL ";
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        foreach ($results as $result) {
            $animalId = intval($result['animal_id']);
            $declareTagReplaceId = intval($result['id']);
            $sql = "UPDATE declare_tag_replace SET animal_id = ".$animalId." WHERE id = ".$declareTagReplaceId;
            $this->em->getConnection()->exec($sql);

            $oldUln = $result['uln_country_code_to_replace'].$result['uln_number_to_replace'];
            $this->setAnimalIdByOldUln($animalId, $oldUln);
        }
        
        return true;
    }


    /**
     * @param int $animalId
     * @param string $oldUln
     */
    private function setAnimalIdByOldUln($animalId, $oldUln)
    {
        if(array_key_exists($oldUln, $this->declareTagReplaceIdByOldUlns)) {
            $declareTagReplaceId = $this->declareTagReplaceIdByOldUlns[$oldUln];

            $sql = "UPDATE declare_tag_replace SET animal_id = ".$animalId." WHERE id = ".$declareTagReplaceId;
            $this->em->getConnection()->exec($sql);
            unset($this->declareTagReplaceIdByOldUlns[$oldUln]);

            //Recursively search for old ulns
            if(array_key_exists($oldUln, $this->declareTagReplaceIdByOldUlns)) {
                $olderUln = $this->oldUlnsByNewUlns[$oldUln];
                $this->setAnimalIdByOldUln($animalId, $olderUln);
            }
        }
    }

}