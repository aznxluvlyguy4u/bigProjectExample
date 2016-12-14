<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface MeasurementAPIControllerInterface {
    function editExteriorMeasurements(Request $request, $ulnString, $measurementDate);
    function getAllowedExteriorKinds(Request $request, $ulnString);
    function getAllowedInspectorsForMeasurements(Request $request);
}