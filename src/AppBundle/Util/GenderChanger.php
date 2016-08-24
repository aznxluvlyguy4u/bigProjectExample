<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;

class GenderChanger
{

    /**
     * @param Animal $animal
     * @return Ewe|null
     */
    public function makeFemale($animal)
    {
        if($animal instanceof Ewe) {
            return $animal;

        } elseif ($animal instanceof Ram) {
            

        } elseif ($animal instanceof Neuter) {


        } else {
            return null;
        }
    }
    
}