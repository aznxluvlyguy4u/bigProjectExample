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

    /*
     * Sub menu
     */
    const DATABASE_SEQUENCE_UPDATE = 1;
    const UNLOCK_ALL_PROCESSES = 2;

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

    private static function getSelectedNamedOption($option): array {
        switch ($option) {
            case MainCommandNamedOptions::DATABASE_SEQUENCE_UPDATE: return [
                MainCommandUtil::FIX_DATABASE_VALUES,
                MainCommandUtil::DATABASE_SEQUENCE_UPDATE
            ];
            case MainCommandNamedOptions::PROCESS_UNLOCK_ALL: return [
                MainCommandUtil::PROCESSOR_LOCKER_OPTIONS,
                MainCommandUtil::UNLOCK_ALL_PROCESSES
            ];
            default: throw new InvalidInputCliException("Invalid option: " . $option .
                ". Valid named options: " . implode(',',MainCommandNamedOptions::getConstants()));
        }
    }
}
