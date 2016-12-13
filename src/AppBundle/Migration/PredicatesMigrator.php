<?php


namespace AppBundle\Migration;

use AppBundle\Entity\Predicate;
use AppBundle\Entity\PredicateRepository;
use AppBundle\Enumerator\PredicateType;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class PredicatesMigrator extends MigratorBase
{
    //ArrayConstants
    const PREDICATE_SCORE = 'predicate_score';
    const PREDICATE_VALUE = 'predicate_value';
    const START_DATE = 'start_date';
    const END_DATE = 'end_date';

    //NullFillers
    const PREDICATE_START_DATE_NULL_FILLER = '1899-01-01';

    /**
     * PredicatesMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data);
    }


    /**
     * @return bool
     */
    public function migrate()
    {
        //1. Create predicateSearchArrays with latest startDates
        $latestStartDateSearchArray = new ArrayCollection();
        $latestCsvPredicatesByAnimalId = new ArrayCollection();

        $animalIdByVsmIdSearchArray = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        foreach ($this->data as $records) {

            $vsmId = $records[0];
            //Skip data for missing animals
            if(array_key_exists($vsmId, $animalIdByVsmIdSearchArray)) {
                $animalId = $animalIdByVsmIdSearchArray[$vsmId];
                $startDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($records[1]);
                $endDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($records[2]);
                $predicateScore = $records[3] != '' ? $records[3] : null;
                $predicateValue = $records[4];

                $isLatestRecord = false;
                if($latestStartDateSearchArray->containsKey($animalId)) {
                    $latestStartDate = $latestStartDateSearchArray->get($animalId);

                    if($latestStartDate == null && $startDate != null) {
                        $latestStartDateSearchArray->set($animalId, $startDate);
                        $isLatestRecord = true;
                    } else if($startDate >= $latestStartDate) {
                        $latestStartDateSearchArray->set($animalId, $startDate);
                        $isLatestRecord = true;
                    }
                } else {
                    $latestStartDateSearchArray->set($animalId, $startDate);
                    $isLatestRecord = true;
                }

                if($isLatestRecord) {
                    $latestCsvPredicatesByAnimalId->set($animalId,
                        [ self::START_DATE => $startDate,
                            self::END_DATE => $endDate,
                            self::PREDICATE_SCORE => $predicateScore,
                            self::PREDICATE_VALUE => $predicateValue,
                        ]);
                }
            }
        }

        //2. Get searchArrays of current data
        $sql = "SELECT animal_id, start_date, end_date, predicate, predicate_score FROM predicate";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $predicatesFromDbSearchArray = new ArrayCollection();
        foreach ($results as $result) {

            $animalId = $result['animal_id'];
            $startDate = TimeUtil::getDateTimeFromNullCheckedDateString($result['start_date']);
            $endDate = TimeUtil::getDateTimeFromNullCheckedDateString($result['end_date']);
            $predicateScore = $result['predicate_score'];
            $predicateValue = $result['predicate'];

            $predicatesFromDbSearchArray->set($animalId,
                [ self::START_DATE => $startDate,
                    self::END_DATE => $endDate,
                    self::PREDICATE_SCORE => $predicateScore,
                    self::PREDICATE_VALUE => $predicateValue,
                ]);
        }

        $newCount = 0;
        $totalCount = 0;
        $animalIds = $latestCsvPredicatesByAnimalId->getKeys();
        $this->cmdUtil->setStartTimeAndPrintIt(count($animalIds)+1, 1);
        foreach ($animalIds as $animalId) {

            $csvPredicateData = $latestCsvPredicatesByAnimalId->get($animalId);
            $csvStartDate = $csvPredicateData[self::START_DATE];
            $csvEndDate = $csvPredicateData[self::END_DATE];
            $csvPredicateValue = Translation::getEnglish($csvPredicateData[self::PREDICATE_VALUE]);

            $csvPredicateScore = null;
            //Only save scores of StarEwes
            if($csvPredicateValue == PredicateType::STAR_EWE_1 || $csvPredicateValue == PredicateType::STAR_EWE_2 ||
                $csvPredicateValue == PredicateType::STAR_EWE_3 || $csvPredicateValue == PredicateType::STAR_EWE) {
                $retrievedCsvPredicateScore = $csvPredicateData[self::PREDICATE_SCORE];
                //Ignore 0 scores
                $csvPredicateScore = $retrievedCsvPredicateScore == 0 ? null : $retrievedCsvPredicateScore;
            }

            $persistNewPredicate = true;
            if($predicatesFromDbSearchArray->containsKey($animalId)) {
                $dbPredicateData = $predicatesFromDbSearchArray->get($animalId);
                $dbStartDate = $dbPredicateData[self::START_DATE];
                $dbEndDate = $dbPredicateData[self::END_DATE];
                $dbPredicateScore = $dbPredicateData[self::PREDICATE_SCORE];
                $dbPredicateValue = $dbPredicateData[self::PREDICATE_VALUE];

                if($csvStartDate < $dbStartDate) {
                    $persistNewPredicate = false;
                } elseif ($csvStartDate == $dbStartDate && $csvPredicateScore == $dbPredicateScore
                    && $csvPredicateValue == $dbPredicateValue) {
                    $persistNewPredicate = false;
                }
            }


            if($persistNewPredicate) {

                $csvStartDateString = TimeUtil::getTimeStampForSql($csvStartDate);
                $csvEndDateString = TimeUtil::getTimeStampForSql($csvEndDate);

                $csvStartDateString = $csvStartDateString == null ? "'".self::PREDICATE_START_DATE_NULL_FILLER."'" : "'".$csvStartDateString."'";
                $csvEndDateString = $csvEndDateString == null ? 'NULL' : "'".$csvEndDateString."'";
                $csvPredicateScore = $csvPredicateScore == null ? 'NULL' : $csvPredicateScore;

                $sql = "INSERT INTO predicate (id, animal_id, start_date, end_date, predicate, predicate_score) VALUES (nextval('measurement_id_seq')," . $animalId . "," . $csvStartDateString . "," . $csvEndDateString . ",'" . $csvPredicateValue . "'," . $csvPredicateScore . ")";
                $this->em->getConnection()->exec($sql);

                $newCount++;
            }
            $totalCount++;
            $this->cmdUtil->advanceProgressBar(1);

        }
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->updatePredicateValuesInAnimal();
    }


    public function updatePredicateValuesInAnimal()
    {
        /** @var PredicateRepository $repository */
        $repository = $this->em->getRepository(Predicate::class);
        $repository->setLatestPredicateValuesOnAllAnimals($this->cmdUtil);
        $repository->fillPredicateValuesInAnimalForPredicatesWithoutStartDates($this->cmdUtil);
    }
}