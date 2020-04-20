<?php


namespace AppBundle\Util;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class LoggerUtil
 * @package AppBundle\Util
 */
class LoggerUtil
{
    const OVERWRITE_FORMAT = "\033[%dA";
    const OVERWRITE_ARGS = 2;


    public static function log(?string $message = null, ?LoggerInterface $logger = null, string $logLevel = LogLevel::DEBUG)
    {
        if ($logger && !empty($message))
        {
            switch ($logLevel) {
                case LogLevel::ALERT: $logger->alert($message); break;
                case LogLevel::CRITICAL: $logger->critical($message); break;
                case LogLevel::EMERGENCY: $logger->emergency($message); break;
                case LogLevel::WARNING: $logger->warning($message); break;
                case LogLevel::ERROR: $logger->error($message); break;
                case LogLevel::NOTICE: $logger->notice($message); break;
                case LogLevel::INFO: $logger->info($message); break;
                case LogLevel::DEBUG:
                default:
                    $logger->debug($message); break;
            }
        }
    }


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


    /**
     * @param LoggerInterface $logger
     * @param string $line
     */
    public static function overwriteNoticeLoggerInterface(LoggerInterface $logger, $line)
    {
        $logger->notice(
            sprintf(self::OVERWRITE_FORMAT,self::OVERWRITE_ARGS)
        );
        $logger->notice($line);
    }
}
