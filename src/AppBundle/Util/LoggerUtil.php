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


    public static function log($message = null, ?LoggerInterface $logger = null, string $logLevel = LogLevel::DEBUG,
                               $printKeys = true, $indentLevel = 0)
    {
        if ($logger && !empty($message))
        {
            if (is_array($message)) {
                foreach ($message as $key => $value) {
                    if(is_array($value)) {
                        $arrayStart = $printKeys ? $key.' : {' : '{';
                        $arrayEnd = $printKeys ? str_repeat(' ', strlen($key)).'   }' : '}';

                        self::log($arrayStart, $logger, $logLevel, $printKeys, $indentLevel);
                        self::log($value, $logger, $logLevel, $printKeys, $indentLevel+1);
                        self::log($arrayEnd, $logger, $logLevel, $printKeys, $indentLevel);

                    } else {
                        $value = self::stringValue($value);
                        if ($printKeys) {
                            $value = $key.' : '.$value;
                        }
                        self::log($value, $logger, $logLevel, $printKeys, $indentLevel);
                    }
                }
            } else {
                $message = self::stringValue($message);

                if (is_string($message)) {

                    $message = self::indentText($indentLevel) . $message;

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
        }
    }


    private static function stringValue($var): string
    {
        if (is_string($var)) {
            $output = $var;
        } else if (is_int($var) || is_float($var)) {
            $output = strval($var);
        } else if (is_bool($var)) {
            $output = $var === true ? "true": "false";
        } else {
            $output = '';
        }

        return $output;
    }


    private static function indentText($indentLevel = 1, $indentType = '      '): string
    {
        return str_repeat($indentType, $indentLevel);
    }


    public static function getMemoryUsageInMb(): string
    {
        return intval(memory_get_usage(false)/1048576);
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
