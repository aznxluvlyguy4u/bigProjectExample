<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

/**
 * Class PedigreeCertificate
 */
class PedigreeCertificate
{
    /**
     * Create the data for the PedigreeCertificate.
     * Before this is run, it is assumed all the ulns have been verified.
     *
     * @param EntityManager $em
     * @param Collection $content containing the ulns of multiple animals
     * @return array
     */
    public static function create(EntityManager $em, Collection $content, Client $client)
    {
        $animals = self::getAnimalsInContentArray($em, $content);

        //TODO Generate an array containing properly labelled variables for twig file.

        //FIXME this is a mock result
        $result = array(
//        'some'  => $vars //Just an example
        );

        return $result;
    }

    /**
     * @param EntityManager $em
     * @param Collection $content
     * @return ArrayCollection
     */
    private static function getAnimalsInContentArray(EntityManager $em, Collection $content)
    {
        $animals = new ArrayCollection();

        foreach ($content->getKeys() as $key) {
            if ($key == Constant::ANIMALS_NAMESPACE) {
                $animalArrays = $content->get($key);

                foreach ($animalArrays as $animalArray) {
                    $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
                    $ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
                    $animal = $em->getRepository(Animal::class)->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);

                    $animals->add($animals);
                }
            }
        }

        
        return $animals;
    }

}