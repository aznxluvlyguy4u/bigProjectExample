<?php


namespace AppBundle\Serializer\PreSerializer;


use Doctrine\Common\Collections\ArrayCollection;

class CompanyPreSerializer extends PreSerializerBase implements PreSerializerInterface
{
    /**
     * @param ArrayCollection|array|string $input
     * @return ArrayCollection
     */
    static function clean($input)
    {
        $collection = self::preClean($input);

        $subscriptionDateKey = 'subscription_date';

        if ($collection->containsKey($subscriptionDateKey)) {
            $subscriptionDateString = PreSerializerBase::fixInvalidDateTimeString($collection->get($subscriptionDateKey));
            $collection->set($subscriptionDateKey, $subscriptionDateString);
        }

        return $collection;
    }

}