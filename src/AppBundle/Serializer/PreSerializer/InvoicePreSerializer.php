<?php


namespace AppBundle\Serializer\PreSerializer;


use Doctrine\Common\Collections\ArrayCollection;

class InvoicePreSerializer extends PreSerializerBase implements PreSerializerInterface
{
    /**
     * @param ArrayCollection|array|string $input
     * @param boolean $returnAsArray
     * @return ArrayCollection|array
     */
    static function clean($input, $returnAsArray = false)
    {
        $collection = self::preClean($input);

        $companyKey = 'company';

        if ($collection->containsKey($companyKey)) {
            $company = CompanyPreSerializer::clean($collection->get($companyKey), true);
            $collection->set($companyKey, $company);
        }

        return $returnAsArray ? $collection->toArray() : $collection;
    }
}