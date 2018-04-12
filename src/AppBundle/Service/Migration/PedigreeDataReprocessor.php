<?php


namespace AppBundle\Service\Migration;


use AppBundle\Util\CommandUtil;

class PedigreeDataReprocessor extends PedigreeDataReprocessorBase
{
    public function run(CommandUtil $cmdUtil)
    {
        $startAnimalId = $this->askForStartAnimalId($cmdUtil);

        $this->getPedigreeDataGenerator()->generateBreedAndPedigreeData(
            $this->getAllAnimalsFromDeclareBirth(false, $startAnimalId),
            $cmdUtil,
            $startAnimalId
        );
    }
}