<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Animal;
use AppBundle\Entity\BlindnessFactor;
use AppBundle\Entity\BlindnessFactorRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class BlindnessFactorsMigrator extends MigratorBase
{
    /**
     * BlindnessFactorsMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data);
    }
    
    public function migrate()
    {
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);

        $sql = "SELECT animal_id, log_date, blindness_factor FROM blindness_factor";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $blindnessFactorSearchArray = [];
        foreach ($results as $result) {
            $blindnessFactorSearchArray[$result['animal_id']] = $result['blindness_factor'];
        }
        $animalIdByVsmIdSearchArray = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $newCount = 0;
        foreach ($this->data as $record) {

            $vsmId = $record[0];

            if(array_key_exists($vsmId, $animalIdByVsmIdSearchArray)) {

                $animalId = $animalIdByVsmIdSearchArray[$vsmId];

                if (!array_key_exists($animalId, $blindnessFactorSearchArray)) {
                    $blindnessFactorValue = Translation::getEnglish($record[1]);
                    $logDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($record[2]);
                    /** @var Animal $animal */
                    $animal = $this->animalRepository->find($animalId);
                    $blindnessFactor = new BlindnessFactor($animal, $blindnessFactorValue, $logDate);
                    $this->em->persist($blindnessFactor);
                    $newCount++;
                }
            }
            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->updateBlindnessFactorValuesInAnimal();
    }

    private function updateBlindnessFactorValuesInAnimal()
    {
        /** @var BlindnessFactorRepository $repository */
        $repository = $this->em->getRepository(BlindnessFactor::class);
        $repository->setLatestBlindnessFactorsOnAllAnimals($this->cmdUtil);
    }
}