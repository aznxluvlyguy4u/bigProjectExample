<?php


namespace AppBundle\MixBlup;

/**
 * Interface MixBlupInputProcessInterface
 * @package AppBundle\MixBlup
 */
interface MixBlupInputProcessInterface
{
    /**
     * Multiple instruction files might use the same datafile.
     * Thus this output should by an array containing one or multiple instruction file arrays,
     * labelled by a key denoting their type.
     *
     * @return array of arrays containing the separate instruction files records.
     */
    function generateInstructionFiles();

    /**
     * @return array containing the datafile records.
     */
    function generateDataFile();

    /**
     * The pedigreeFile specifically related to its datafile.
     * If no specific datafile is possible, this will just return the default complete pedigreeFile.
     *
     * @return array containing the pedigreeFile records
     */
    function generatePedigreeFile();

    /**
     * Writes the instructionFile-, dataFile- and pedigreeFile data to their respective text input files.
     *
     * @return boolean with true indicating if writes were successful
     */
    function write();

    /**
     * Writes the instructionFile data to their respective text input files.
     *
     * @return boolean with true indicating if writes were successful
     */
    function writeInstructionFiles();
}