<?php


namespace AppBundle\Migration;


use AppBundle\Component\MessageBuilderBase;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\ActionType;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class TagReplaceMigratorOld extends MigratorBase
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
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);
        $animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmId();

        $sql = "SELECT uln_country_code_replacement, uln_country_code_to_replace, uln_number_to_replace, uln_number_replacement FROM declare_tag_replace";
        $results = $this->conn->query($sql)->fetchAll();
        $ulnReplacementsInDatabase = [];
        foreach ($results as $result) {
            $uln = $result['uln_country_code_replacement'].' '.$result['uln_number_replacement'];
            $ulnReplacementsInDatabase[$uln] = $uln;
        }

        $newCount = 0;
        foreach ($this->data as $record) {

            $ulnNew = $record[3];

            //Check if record was already saved in the database
            if(!array_key_exists($ulnNew, $ulnReplacementsInDatabase)) {

                $vsmId = $record[0];
                $dateTimeString = $record[1];
                $dateTime = $dateTimeString == null || $dateTimeString == '' ? self::getBlankDateFillerDateTime() : new \DateTime($dateTimeString);
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

                    $declareTagTransfer->setAnimalOrderNumberReplacement(StringUtil::getLast5CharactersFromString($newUlnNumber));
                    $declareTagTransfer->setAnimalOrderNumberToReplace(StringUtil::getLast5CharactersFromString($oldUlnNumber));

                    $declareTagTransfer->setAnimalType(AnimalType::sheep);
                    $declareTagTransfer->setReplaceDate($dateTime);
                    $declareTagTransfer->setLogDate(new \DateTime());
                    $declareTagTransfer->setRequestState(RequestStateType::IMPORTED);
                    $requestId = MessageBuilderBase::getNewRequestId();
                    $declareTagTransfer->setRequestId($requestId);
                    $declareTagTransfer->setMessageId($requestId);
                    $declareTagTransfer->setAction(ActionType::V_MUTATE);
                    $declareTagTransfer->setRecoveryIndicator(RecoveryIndicatorType::N);
                    $declareTagTransfer->setRelationNumberKeeper(RequestStateType::IMPORTED);
                    $declareTagTransfer->setUbn(RequestStateType::IMPORTED);
                    $declareTagTransfer->setActionBy($this->developer);

                    $animalId = null;
                    if($animalIdsByVsmId->containsKey($vsmId)) {
                        $animal = $this->animalRepository->find($animalIdsByVsmId->get($vsmId));
                        $declareTagTransfer->setAnimal($animal);
                        $animalId = $animal->getId();
                    }
                    $this->em->persist($declareTagTransfer);
                    $this->insertReplacedTag($oldUlnCountryCode, $oldUlnNumber, $dateTime, $animalId);
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
     * @param string $oldUlnCountryCode
     * @param string $oldUlnNumber
     * @param \DateTime $orderDate
     * @param int $animalIdUlnHistory
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function insertReplacedTag($oldUlnCountryCode, $oldUlnNumber, $orderDate, $animalIdUlnHistory)
    {
        if(!is_string($oldUlnCountryCode) || !is_string($oldUlnNumber)) { return null; }

        $animalIdInReplacedTag = SqlUtil::NULL;
        $animalOrderNumber =  StringUtil::getLast5CharactersFromString($oldUlnNumber);
        $dateString = $orderDate != null ? $orderDate->format(SqlUtil::DATE_FORMAT) : self::getBlankDateFillerDateString();

        $sql = "SELECT MAX(id) FROM tag";
        $maxId = $this->conn->query($sql)->fetch()['max'];
        $tagId = $maxId + 1;
        
        $sql = "INSERT INTO tag (id, animal_id, tag_status, animal_order_number, order_date, uln_country_code, uln_number) VALUES (".$tagId.",".$animalIdInReplacedTag.",'".TagStateType::REPLACED."','".$animalOrderNumber."','". $dateString."','".$oldUlnCountryCode."','".$oldUlnNumber."')";
        $this->conn->exec($sql);

        if(is_int($animalIdUlnHistory)) {
            $sql = "INSERT INTO ulns_history (tag_id, animal_id) VALUES (".$tagId.",".$animalIdUlnHistory.")";
            $this->conn->exec($sql);
        }
    }



    /**
     * @return bool
     */
    public function setAnimalIdsOnDeclareTagReplaces()
    {
        //Create SearchArrays
        $sql = "SELECT id, uln_country_code_to_replace, uln_number_to_replace, uln_country_code_replacement, uln_number_replacement FROM declare_tag_replace
                WHERE animal_id ISNULL ";
        $results = $this->conn->query($sql)->fetchAll();
        
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
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $animalId = intval($result['animal_id']);
            $declareTagReplaceId = intval($result['id']);
            $sql = "UPDATE declare_tag_replace SET animal_id = ".$animalId." WHERE id = ".$declareTagReplaceId;
            $this->conn->exec($sql);

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
            $this->conn->exec($sql);
            unset($this->declareTagReplaceIdByOldUlns[$oldUln]);

            //Recursively search for old ulns
            if(array_key_exists($oldUln, $this->declareTagReplaceIdByOldUlns)) {
                $olderUln = $this->oldUlnsByNewUlns[$oldUln];
                $this->setAnimalIdByOldUln($animalId, $olderUln);
            }
        }
    }

}