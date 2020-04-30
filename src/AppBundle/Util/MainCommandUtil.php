<?php


namespace AppBundle\Util;


use AppBundle\Enumerator\MainCommandNamedOptions;
use AppBundle\Exception\Cli\InvalidInputCliException;
use Symfony\Component\Console\Input\InputInterface;

class MainCommandUtil
{
    /*
     * Main menu
     */
    const FIX_DATABASE_VALUES = 6;
    const INITIALIZE_DATABASE_VALUES = 8;
    const PROCESSOR_LOCKER_OPTIONS = 14;
    const INBREEDING_COEFFICIENT_PROCESS_OPTIONS = 16;

    /*
     * Sub menu
     */
    const DATABASE_SEQUENCE_UPDATE = 1;
    const UNLOCK_ALL_PROCESSES = 2;

    // Inbreeding coefficient process
    const IC_ALL_ANIMALS_RUN = 4;
    const IC_REPORT_RUN = 6;
    const IC_PARENT_PAIR_RUN = 9;

    /**
     * @param InputInterface $input
     * @return array
     * @throws InvalidInputCliException
     */
    public static function getSelectedOptions(InputInterface $input) {
        $optionsString = ltrim($input->getOption('option'),'=');

        if (empty($optionsString)) {
            return [];
        }

        $optionsList = explode(',', $optionsString);
        foreach ($optionsList as $option) {
            if (!ctype_digit($option)) {
                return self::getSelectedNamedOption($option);
            }
        }
        return $optionsList;
    }

    public static function namedOptionsAsList(): string
    {
        return "\n- " . implode("\n- ",MainCommandNamedOptions::getConstants());
    }

    private static function getSelectedNamedOption($option): array {
        switch ($option) {
            case MainCommandNamedOptions::DATABASE_SEQUENCE_UPDATE: return [
                MainCommandUtil::FIX_DATABASE_VALUES,
                MainCommandUtil::DATABASE_SEQUENCE_UPDATE
            ];
            case MainCommandNamedOptions::INBREEDING_COEFFICIENT_RUN_ALL_ANIMALS: return [
                MainCommandUtil::INBREEDING_COEFFICIENT_PROCESS_OPTIONS,
                MainCommandUtil::IC_ALL_ANIMALS_RUN
            ];
            case MainCommandNamedOptions::INBREEDING_COEFFICIENT_RUN_REPORT: return [
                MainCommandUtil::INBREEDING_COEFFICIENT_PROCESS_OPTIONS,
                MainCommandUtil::IC_REPORT_RUN
            ];
            case MainCommandNamedOptions::INBREEDING_COEFFICIENT_RUN_PARENT_PAIRS: return [
                MainCommandUtil::INBREEDING_COEFFICIENT_PROCESS_OPTIONS,
                MainCommandUtil::IC_PARENT_PAIR_RUN
            ];
            case MainCommandNamedOptions::PROCESS_UNLOCK_ALL: return [
                MainCommandUtil::PROCESSOR_LOCKER_OPTIONS,
                MainCommandUtil::UNLOCK_ALL_PROCESSES
            ];
            default: throw new InvalidInputCliException("Invalid option: " . $option .
                ". Valid named options: " . self::namedOptionsAsList());
        }
    }
}
