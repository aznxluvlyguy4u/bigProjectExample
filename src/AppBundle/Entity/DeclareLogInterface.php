<?php


namespace AppBundle\Entity;

use AppBundle\Enumerator\Language;


/**
 * Interface DeclareLogInterface
 */
interface DeclareLogInterface
{
    const DECLARE_LOG_MESSAGE_NULL_RESPONSE = '';
    const EVENT_DATE_NULL_RESPONSE = '';
    const EVENT_DATE_FORMAT = 'd-m-Y';

    /** @return string */
    function getDeclareLogMessage($language = Language::EN);
    /** @return string */
    function getEventDate();
}