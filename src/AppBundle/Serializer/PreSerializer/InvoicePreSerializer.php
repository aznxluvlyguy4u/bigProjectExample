<?php


namespace AppBundle\Serializer\PreSerializer;


use Doctrine\Common\Collections\ArrayCollection;

class InvoicePreSerializer extends PreSerializerBase implements PreSerializerInterface
{
    /**
     * @param ArrayCollection|array|string $input
     * @return ArrayCollection
     */
    static function clean($input)
    {
        $collection = self::preClean($input);

        $companyKey = 'company';

        if ($collection->containsKey($companyKey)) {
            $company = CompanyPreSerializer::clean($collection->get($companyKey));
            $collection->set($companyKey, $company);
        }

        return $collection;
    }
}