<?php


namespace AppBundle\Entity;


interface DeclareAnimalDataInterface
{
    /** @return Animal */
    function getAnimal();
    function getUlnCountryCode();
    function getUlnNumber();
}
