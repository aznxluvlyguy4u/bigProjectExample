<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface MeasurementAPIControllerInterface {
    function createExteriorMeasurement(Request $request, $ulnString);
    function editExteriorMeasurement(Request $request, $ulnString, $measurementDate);
    function getAllowedExteriorKinds(Request $request, $ulnString);
    function getAllowedInspectorsForMeasurements(Request $request);
}