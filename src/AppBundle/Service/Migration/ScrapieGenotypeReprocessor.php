<?php


namespace AppBundle\Service\Migration;


class ScrapieGenotypeReprocessor extends PedigreeDataReprocessorBase
{
    public function run()
    {
        $this->getPedigreeDataGenerator()->generateScrapieGenotypeData(
            $this->getAllAnimalsFromDeclareBirth(true)
        );
    }
}