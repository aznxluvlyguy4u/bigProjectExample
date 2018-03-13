<?php


namespace AppBundle\Serializer\PreSerializer;


use Doctrine\Common\Collections\ArrayCollection;

interface PreSerializerInterface
{
    /**
     * @param ArrayCollection|array|string $input
     * @param boolean $returnAsArray
     * @return ArrayCollection|array
     */
    static function clean($input, $returnAsArray);
}