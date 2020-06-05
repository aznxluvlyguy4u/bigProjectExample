<?php

namespace AppBundle\Enumerator;

use AppBundle\Exception\Cli\InvalidInputCliException;
use AppBundle\Traits\EnumInfo;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ActionType
 * @package AppBundle\Enumerator
 */
class ProcessCommandOptions
{
    use EnumInfo;

    const START = 'start';
    const CANCEL = 'cancel';
    const RUN = 'run';

    /**
     * @param InputInterface $input
     * @return string
     */
    public static function getSelectedOptions(InputInterface $input): string {
        $option = ltrim($input->getOption('option'),'=');
        self::validateOption($option);
        return $option;
    }

    public static function optionsAsList(): string
    {
        return "\n- " . implode("\n- ",self::getConstants());
    }

    private static function validateOption($option) {
        if (!in_array($option, self::getConstants())) {
            throw new InvalidInputCliException(self::invalidInputText($option));
        }
    }

    public static function invalidInputText($option): string
    {
        return "Invalid option: " . $option . ". Valid options: " . self::optionsAsList();
    }
}
