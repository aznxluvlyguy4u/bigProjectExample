<?php


namespace AppBundle\Serializer\PreSerializer;


use Doctrine\Common\Collections\ArrayCollection;

class CompanyPreSerializer extends PreSerializerBase implements PreSerializerInterface
{
    /**
     * @param ArrayCollection|array|string $input
     * @param boolean $returnAsArray
     * @return ArrayCollection
     */
    static function clean($input, $returnAsArray = false)
    {
        $collection = self::preClean($input);

        $subscriptionDateKey = 'subscription_date';

        if ($collection->containsKey($subscriptionDateKey)) {
            $subscriptionDateString = PreSerializerBase::fixInvalidDateTimeString($collection->get($subscriptionDateKey));
            $collection->set($subscriptionDateKey, $subscriptionDateString);
        }

        return $returnAsArray ? $collection->toArray() : $collection;
    }

}