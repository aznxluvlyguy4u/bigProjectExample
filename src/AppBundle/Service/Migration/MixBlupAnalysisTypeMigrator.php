<?php


namespace AppBundle\Service\Migration;


use AppBundle\Constant\MixBlupAnalysis;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\MixBlupAnalysisType;
use AppBundle\Entity\MixBlupAnalysisTypeRepository;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

class MixBlupAnalysisTypeMigrator extends MigratorServiceBase implements IMigratorService
{
    const IMPORT_SUB_FOLDER = 'initial_values/';
    const BREED_VALUE_TYPE_ANALYSIS_TYPE = 'finder_breed_value_type_analysis_type';

    /** @var MixBlupAnalysisTypeRepository */
    private $mixBlupAnalysisTypeRepository;

    /** @var MixBlupAnalysis[] */
    private $analysisTypes;
    /** @var BreedValueType[] */
    private $breedValueTypes;

    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
        $this->mixBlupAnalysisTypeRepository = $this->em->getRepository(MixBlupAnalysisType::class);

        $this->filenames = array(
            self::BREED_VALUE_TYPE_ANALYSIS_TYPE => 'breed_value_type_analysis_type.csv',
        );

        $this->getCsvOptions()->setPipeSeparator();

        $this->analysisTypes = [];
        $this->breedValueTypes = [];
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
        $newCount = 0;
        foreach (MixBlupType::getConstants() as $en => $nl) {
            $currentType = $this->getAnalysisTypeByEn($en);
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
     * @param string $en
     * @return MixBlupAnalysisType|null
     */
    private function getAnalysisTypeByEn($en)
    {
        if (count($this->analysisTypes) === 0) {
            $this->analysisTypes = $this->mixBlupAnalysisTypeRepository->findAll();
        }

        /** @var MixBlupAnalysisType $currentType */
        foreach ($this->analysisTypes as $currentType) {
            if ($currentType->getEn() === $en) {
                return $currentType;
            }
        }
        return null;
    }


    /**
     * @param string $nl
     * @return MixBlupAnalysisType|null
     */
    private function getAnalysisTypeByNl($nl)
    {
        if (count($this->analysisTypes) === 0) {
            $this->analysisTypes = $this->mixBlupAnalysisTypeRepository->findAll();
        }

        /** @var MixBlupAnalysisType $currentType */
        foreach ($this->analysisTypes as $currentType) {
            if ($currentType->getNl() === $nl) {
                return $currentType;
            }
        }
        return null;
    }


    private function setAnalysisTypesOnBreedValueTypes()
    {
        $csv = $this->parseCSV(self::BREED_VALUE_TYPE_ANALYSIS_TYPE);

        $breedValueTypes = [];
        /** @var BreedValueType $breedValueType */
        foreach($this->em->getRepository(BreedValueType::class)->findAll() as $breedValueType) {
            $breedValueTypes[$breedValueType->getNl()] = $breedValueType;
        }

        $updateCount = 0;
        foreach ($csv as $record) {
            $breedValueTypeNl = $record[0];
            $analysisTypeNl = $record[1];

            if ($breedValueTypeNl === null && $analysisTypeNl === null) {
                continue;
            }

            $analysisType = $this->getAnalysisTypeByNl($analysisTypeNl);
            $breedValueType = ArrayUtil::get($breedValueTypeNl, $breedValueTypes);

            if ($analysisType === null) {
                throw new \Exception('AnalysisType not found for ' . $analysisTypeNl);
            }

            if ($breedValueType === null) {
                throw new \Exception('BreedValueType not found for ' . $breedValueTypeNl);
            }

            if ($breedValueType->getMixBlupAnalysisType() === null
            || $breedValueType->getMixBlupAnalysisType()->getId() !== $analysisType->getId())
            {
                $breedValueType->setMixBlupAnalysisType($analysisType);
                $this->em->persist($breedValueType);
                $updateCount++;
            }
        }

        if ($updateCount > 0) {
            $this->em->flush();
        }

        $message = ($updateCount == 0 ? 'No' : $updateCount) . ' new mixBlupAnalysisType linked';
        $this->writeln($message);

        return $updateCount;
    }
}