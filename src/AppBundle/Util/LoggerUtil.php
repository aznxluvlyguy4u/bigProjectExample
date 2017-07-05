<?php


namespace AppBundle\Util;

use Symfony\Bridge\Monolog\Logger;

/**
 * Class LoggerUtil
 * @package AppBundle\Util
 */
class LoggerUtil
{
    const OVERWRITE_FORMAT = "\033[%dA";
    const OVERWRITE_ARGS = 2;


    /**
     * @param Logger $logger
     * @param string $line
     */
    public static function overwriteDebug(Logger $logger, $line)
    {
        $logger->debug(sprintf(self::OVERWRITE_FORMAT, self::OVERWRITE_ARGS));
        $logger->debug($line);
    }


    /**
     * @param Logger $logger
     * @param string $line
     */
    public static function overwriteNotice(Logger $logger, $line)
    {
        $logger->notice(sprintf(self::OVERWRITE_FORMAT, self::OVERWRITE_ARGS));
        $logger->notice($line);
    }
}