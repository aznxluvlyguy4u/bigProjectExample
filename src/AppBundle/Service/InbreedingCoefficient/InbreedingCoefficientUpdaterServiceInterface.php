<?php


namespace AppBundle\Service\InbreedingCoefficient;


interface InbreedingCoefficientUpdaterServiceInterface
{
    public function generateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false);
    public function regenerateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false);
    public function generateForAllAnimalsAndLitters();
    public function regenerateForAllAnimalsAndLitters();
    public function generateForAnimalsAndLittersOfUbn(string $ubn);
    public function regenerateForAnimalsAndLittersOfUbn(string $ubn);
}
