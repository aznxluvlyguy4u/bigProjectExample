<?php

namespace AppBundle\Service\Migration;


use AppBundle\Entity\TreatmentType;
use AppBundle\Entity\TreatmentTypeRepository;
use AppBundle\Enumerator\ImportFileName;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\MigrationUtil;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class TreatmentTypeInitializer
 */
class TreatmentTypeInitializer extends MigratorServiceBase
{
    const DELETE_NEW_RECORDS = false;

    /** @var TreatmentTypeRepository */
    private $repository;

    /**
     * @param EntityManagerInterface $em
     * @param string $rootDir
     */
    public function __construct(EntityManagerInterface $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
        $this->repository = $em->getRepository(TreatmentType::class);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return bool
     * @throws \Exception
     */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        if(!$this->parse()) { return false; }

        $allowedTypes = TreatmentTypeOption::getConstants();

        //Validate csv file first
        foreach ($this->data as $record) {
            $type = $record[0];
            $description = $record[1];

            if (!key_exists($type, $allowedTypes)) {
                throw new \Exception('Type: ['.$type.'] is not allowed! Only allowed types: '.implode(', ', $allowedTypes));
            }
        }


        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);

        $treatmentTypes = $this->repository->findAll();

        $descriptionSearchArray = [];
        $deleteSearchArray = [];

        /** @var TreatmentType $treatmentType */
        foreach ($treatmentTypes as $treatmentType) {
            $descriptionSearchArray[$treatmentType->getDescription()] = $treatmentType;
            $deleteSearchArray[$treatmentType->getDescription()] = $treatmentType;
        }

        $newCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        foreach ($this->data as $record) {

            $type = $record[0];
            $description = $record[1];

            if (!key_exists($description, $descriptionSearchArray)) {
                $treatmentType = (new TreatmentType())
                    ->setCreationBy($this->getDeveloper())
                    ->setDescription($description)
                    ->setType($type)
                ;
                $this->em->persist($treatmentType);
                $newCount++;

            } else {
                $treatmentType = $descriptionSearchArray[$description];
                if ($treatmentType->getType() !== $type) {
                    $treatmentType
                        ->setType($type)
                        ->setEditedBy($this->getDeveloper())
                        ->setLogDate(new \DateTime())
                    ;
                    $this->em->persist($treatmentType);
                    $updateCount++;
                }
            }

            //Remove from deleteList
            if(key_exists($description, $deleteSearchArray)) {
                unset($deleteSearchArray[$description]);
            }
            $this->cmdUtil->advanceProgressBar(1);
        }

        if(self::DELETE_NEW_RECORDS) {
            //Delete obsolete records
            /** @var TreatmentType $treatmentType */
            foreach ($treatmentTypes as $treatmentType) {
                $this->em->remove($treatmentType);
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
        $csvOptions = MigrationUtil::createInitialValuesFolderCsvImport(ImportFileName::TREATMENT_TYPES, $this->rootDir);

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