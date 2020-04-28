<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\Person;

interface InbreedingCoefficientReportUpdaterServiceInterface extends InbreedingCoefficientUpdaterServiceInterface
{
    function add(int $workerId, array $ramIds, array $eweIds);
    function generateReport(
        array $ramIds, array $eweIds, ?int $workerId,
        Person $actionBy, string $fileType, string $locale
    );
}
