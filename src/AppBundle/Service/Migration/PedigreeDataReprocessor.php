<?php


namespace AppBundle\Service\Migration;


class PedigreeDataReprocessor extends PedigreeDataReprocessorBase
{
    public function run()
    {
        $this->getPedigreeDataGenerator()->generateBreedAndPedigreeData(
            $this->getAllAnimalsFromDeclareBirth(false)
        );
    }
}