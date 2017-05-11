<?php


namespace AppBundle\MixBlup;


interface MixBlupInstructionFileInterface
{
    /**
     * Multiple instruction files might use the same datafile.
     * Thus this output should by an array containing one or multiple instruction file arrays,
     * labelled by a key denoting their type.
     *
     * @return array of arrays containing the separate instruction files records.
     */
    static function generateInstructionFiles();
}