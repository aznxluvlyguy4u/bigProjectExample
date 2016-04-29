<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareDepart
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareDepart")
 * @package AppBundle\Entity
 */
class DeclareDepart extends DeclareBase
{
//TODO


    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareDepart
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }
}

//TODO Add these parameters
//transportAfvoerGegevens
//- afvoerdatum			        >25-03-2016
//- meldingeenheidBestemming	>702511
//- transKenteken			    >01-ABC-2
//- transRelatienummerVervoerder
//- transNaamVervoerder
//- transTijdstipVertrek
//- transVerwachteTransportduur
//- dierCategorie
//- aantalDieren
//- aantalDierenOpBedrijf
//- transportnummer
//- groepsgegevens
//
//
//diergegevensSelRequest
//- selDierLandcode	    >NL
//- selDierLevensnummer	>100004118556
//- selDierWerknummer
//- dierSoort		        >3
//- meldingnummerOorsprong