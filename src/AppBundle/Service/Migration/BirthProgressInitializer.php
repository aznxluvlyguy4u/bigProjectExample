<?php


namespace AppBundle\Service\Migration;


use AppBundle\Entity\BirthProgress;
use AppBundle\Entity\BirthProgressRepository;
use AppBundle\Enumerator\ImportFileName;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\MigrationUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class BirthProgressInitializer
 */
class BirthProgressInitializer extends MigratorServiceBase implements IMigratorService
{
    const SEPARATOR = '||||';
    const DELETE_NEW_RECORDS = false;

    /** @var BirthProgressRepository */
    private $repository;


    /**
     * @param ObjectManager $em
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
        $this->repository = $em->getRepository(BirthProgress::class);
    }

    /**
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        if(!$this->parse()) { return false; }

        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);

        $birthProgresses = $this->repository->findAll();

        $descriptionSearchArray = [];
        $dutchDescriptionSearchArray = [];
        $deleteSearchArray = [];
        /** @var BirthProgress $birthProgress */
        foreach ($birthProgresses as $birthProgress) {
            $descriptionSearchArray[$birthProgress->getDescription()] = $birthProgress;
            $dutchDescriptionSearchArray[$birthProgress->getDutchDescription()] = $birthProgress;
            $deleteSearchArray[$birthProgress->getDescription().self::SEPARATOR.$birthProgress->getDutchDescription()] = $birthProgress;
        }

        $newCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        foreach ($this->data as $record) {

            $description = $record[0];
            $dutchDescription = $record[1];
            $mixBlupScore = intval($record[2]);
            $deleteKey = $description.self::SEPARATOR.$dutchDescription;

            if (!key_exists($description, $descriptionSearchArray)
                && !key_exists($dutchDescription, $dutchDescriptionSearchArray)) {
                $birthProgress = new BirthProgress($description, $dutchDescription, $mixBlupScore);
                $this->em->persist($birthProgress);
                $newCount++;

            } elseif (key_exists($description, $descriptionSearchArray)
                && !key_exists($dutchDescription, $dutchDescriptionSearchArray)) {
                $birthProgress = $descriptionSearchArray[$description];
                $birthProgress->setDutchDescription($dutchDescription)->setMixBlupScore($mixBlupScore);
                $this->em->persist($birthProgress);
                $updateCount++;

            } elseif (!key_exists($description, $descriptionSearchArray)
                && key_exists($dutchDescription, $dutchDescriptionSearchArray)) {
                $birthProgress = $dutchDescriptionSearchArray[$dutchDescription];
                $birthProgress->setDescription($description)->setMixBlupScore($mixBlupScore);
                $this->em->persist($birthProgress);
                $updateCount++;

            } else {
                //Both the description and dutchDescription are identical
                $birthProgress = $descriptionSearchArray[$description];
                if($birthProgress->getMixBlupScore() != $mixBlupScore) {
                    $birthProgress->setMixBlupScore($mixBlupScore);
                    $this->em->persist($birthProgress);
                    $updateCount++;
                }
            }
            //Remove from deleteList
            if(key_exists($deleteKey, $deleteSearchArray)) {
                unset($deleteSearchArray[$deleteKey]);
            }
            $this->cmdUtil->advanceProgressBar(1);
        }

        if(self::DELETE_NEW_RECORDS) {
            //Delete obsolete records
            /** @var BirthProgress $birthProgress */
            foreach ($deleteSearchArray as $birthProgress) {
                $this->em->remove($birthProgress);
                $deleteCount++;
            }
        } elseif(count($deleteSearchArray) > 0) {
            $this->cmdUtil->writeln('Note there are some new records that are not deleted on purpose');
        }

        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage('Records persisted new|updated|deleted: '.$newCount.'|'.$updateCount.'|'.$deleteCount);
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @return bool
     */
    private function parse()
    {
        $csvOptions = MigrationUtil::createInitialValuesFolderCsvImport(ImportFileName::BIRTH_PROGRESS, $this->rootDir);

        if(!FilesystemUtil::csvFileExists($this->rootDir, $csvOptions)) {
            $this->cmdUtil->writeln($csvOptions->getFileName().' is missing. No '.$csvOptions->getFileName().' data is imported!');
            return false;
        }

        $csv = CsvParser::parse($csvOptions);
        if(!is_array($csv)) {
            $this->cmdUtil->writeln('Import file failed or import file is empty');
            return false;
        }

        $this->data = $csv;
        return true;
    }
}