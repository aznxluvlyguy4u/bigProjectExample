<?php


namespace AppBundle\Entity;


use AppBundle\Enumerator\ScrapieGenotypeSourceType;
use AppBundle\Util\ArrayUtil;

class ScrapieGenotypeSourceRepository extends BaseRepository
{
    /** @var ScrapieGenotypeSource[] */
    private $sources;

    private function initializeSourcesByDescription()
    {
        if ($this->sources === null || count($this->sources) === 0) {
            $this->refreshSourcesByDescription();
        }
    }

    private function refreshSourcesByDescription()
    {
        $this->sources = [];
        /** @var ScrapieGenotypeSource $scrapieGenotypeSource */
        foreach ($this->findAll() as $scrapieGenotypeSource) {
            $this->sources[$scrapieGenotypeSource->getDescription()] = $scrapieGenotypeSource;
        }
    }


    public function clearSearchArrays()
    {
        $this->sources = null;
    }


    /**
     * @param string $sourceType
     * @param boolean $withoutSearchArray
     * @return ScrapieGenotypeSource|null
     */
    function getSourceByDescription($sourceType, $withoutSearchArray)
    {
        if ($withoutSearchArray) {
            /** @var ScrapieGenotypeSource $scrapieGenoTypeSource */
            $scrapieGenoTypeSource = $this->findOneBy(['description' => $sourceType]);
            return $scrapieGenoTypeSource;
        }

        $this->initializeSourcesByDescription();
        return ArrayUtil::get($sourceType, $this->sources);
    }


    /**
     * @param boolean $withoutSearchArray
     * @return ScrapieGenotypeSource|null
     */
    function getAdministrativeSource($withoutSearchArray = true)
    {
        return $this->getSourceByDescription(ScrapieGenotypeSourceType::ADMINISTRATIVE, $withoutSearchArray);
    }


    /**
     * @param boolean $withoutSearchArray
     * @return ScrapieGenotypeSource|null
     */
    function getAdminEditSource($withoutSearchArray = true)
    {
        return $this->getSourceByDescription(ScrapieGenotypeSourceType::ADMIN_EDIT, $withoutSearchArray);
    }


    /**
     * @param boolean $withoutSearchArray
     * @return ScrapieGenotypeSource|null
     */
    function getLaboratoryResearchSource($withoutSearchArray = true)
    {
        return $this->getSourceByDescription(ScrapieGenotypeSourceType::LABORATORY_RESEARCH, $withoutSearchArray);
    }


    /**
     * @return int
     */
    public function initializeRecords()
    {
        $sourceDescriptions = ScrapieGenotypeSourceType::getConstants();

        $updateCount = 0;
        foreach ($sourceDescriptions as $sourceDescription) {
            $source = $this->getSourceByDescription($sourceDescription, false);
            if (!$source) {
                $source = new ScrapieGenotypeSource();
                $source->setDescription($sourceDescription);
                $this->getManager()->persist($source);
                $updateCount++;
            }
        }

        if ($updateCount > 0) {
            $this->getManager()->flush();
        }

        $this->clearSearchArrays();

        return $updateCount;
    }
}