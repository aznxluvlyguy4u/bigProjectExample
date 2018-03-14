<?php


namespace AppBundle\Service\Migration;


use AppBundle\Entity\MixBlupAnalysisType;
use AppBundle\Entity\MixBlupAnalysisTypeRepository;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

class MixBlupAnalysisTypeMigrator extends MigratorServiceBase implements IMigratorService
{
    const IMPORT_SUB_FOLDER = 'initial_values/';
    const BREED_VALUE_TYPE_ANALYSIS_TYPE = 'finder_breed_value_type_analysis_type';

    /** @var MixBlupAnalysisTypeRepository */
    private $mixBlupAnalysisTypeRepository;

    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
        $this->mixBlupAnalysisTypeRepository = $this->em->getRepository(MixBlupAnalysisType::class);

        $this->filenames = array(
            self::BREED_VALUE_TYPE_ANALYSIS_TYPE => 'breed_value_type_analysis_type.csv',
        );

        $this->getCsvOptions()->setPipeSeparator();
    }

    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn(CommandTitle::MIXBLUP_ANALYSIS_TYPE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Initialize analysis types', "\n",
            '2: Set analysis types on breed value types', "\n",
            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->initializeAnalysisTypes(); break;
            case 2: $this->setAnalysisTypesOnBreedValueTypes(); break;
            default: $this->writeln('EXIT'); return;
        }
        $this->run($cmdUtil);
    }


    /**
     * @return int
     */
    private function initializeAnalysisTypes()
    {
        $currentTypes = $this->mixBlupAnalysisTypeRepository->findAll();

        $newCount = 0;
        foreach (MixBlupType::getConstants() as $en => $nl) {
            $currentType = $this->getTypeByEn($currentTypes, $en);
            if ($currentType) {
                continue;
            }

            $newType = (new MixBlupAnalysisType())
                ->setEn($en)
                ->setNl($nl)
            ;

            $this->em->persist($newType);
            $newCount++;
        }

        if ($newCount > 0) {
            $this->em->flush();
        }

        $message = ($newCount > 0 ? $newCount : 'No') . ' MixBlupAnalysisTypes were initialized';
        $this->writeLn($message);

        return $newCount;
    }


    /**
     * @param MixBlupAnalysisType[] $currentTypes
     * @param string $en
     * @return MixBlupAnalysisType|null
     */
    private function getTypeByEn($currentTypes, $en)
    {
        /** @var MixBlupAnalysisType $currentType */
        foreach ($currentTypes as $currentType) {
            if ($currentType->getEn() === $en) {
                return $currentType;
            }
        }
        return null;
    }


    private function setAnalysisTypesOnBreedValueTypes()
    {
        //TODO
    }
}