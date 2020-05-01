<?php


namespace AppBundle\Service\InbreedingCoefficient;


interface InbreedingCoefficientAllAnimalsUpdaterServiceInterface extends InbreedingCoefficientUpdaterServiceInterface
{
    public function start(bool $recalculate = false);
}
