<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface ScanMeasurementsAPIControllerInterface
{
    function getScanMeasurements(Request $request, $animalId);
    function deleteScanMeasurements(Request $request, $animalId);
    function modifyScanMeasurements(Request $request, $animalId);
}
