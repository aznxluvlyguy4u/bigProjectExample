<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface BirthMeasurementAPIControllerInterface {
    function editBirthMeasurements(Request $request, $animalId);
}