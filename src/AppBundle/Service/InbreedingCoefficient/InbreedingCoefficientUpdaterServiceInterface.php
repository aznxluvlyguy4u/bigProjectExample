<?php


namespace AppBundle\Service\InbreedingCoefficient;


interface InbreedingCoefficientUpdaterServiceInterface
{
    public function run(): bool;
    public function cancel(): bool;
    public function displayAll();
    public function unlockAll();
}
