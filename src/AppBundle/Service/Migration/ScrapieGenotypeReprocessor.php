<?php


namespace AppBundle\Service\Migration;


use AppBundle\Util\CommandUtil;

class ScrapieGenotypeReprocessor extends PedigreeDataReprocessorBase
{
    public function run(CommandUtil $commandUtil)
    {
        $startAnimalId = $this->askForStartAnimalId($commandUtil);

        $this->getPedigreeDataGenerator()->generateScrapieGenotypeData(
            $this->getAllAnimalsFromDeclareBirth(true, $startAnimalId),
            null,
            $commandUtil,
            $startAnimalId
        );
    }
}