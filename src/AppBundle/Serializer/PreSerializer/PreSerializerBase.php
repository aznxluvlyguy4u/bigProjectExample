<?php


namespace AppBundle\Serializer\PreSerializer;


use Doctrine\Common\Collections\ArrayCollection;

class PreSerializerBase
{

    /**
     * @param string|ArrayCollection|array $input
     * @return ArrayCollection
     */
    public static function preClean($input)
    {
        if (is_array($input)) {
            return new ArrayCollection($input);
        } elseif ($input instanceof ArrayCollection) {
            return $input;
        }

        return new ArrayCollection(json_decode($input, true));
    }


    public static function postClean($json)
    {

    }


    /**
     * @param string $dateTimeString
     * @return null|string
     */
    public static function fixInvalidDateTimeString($dateTimeString)
    {
        return $dateTimeString == '' ? null : $dateTimeString;
    }
}