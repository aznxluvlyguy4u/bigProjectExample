<?php


namespace AppBundle\Serializer\PreSerializer;


use Doctrine\Common\Collections\ArrayCollection;

interface PreSerializerInterface
{
    static function clean($collection);
}