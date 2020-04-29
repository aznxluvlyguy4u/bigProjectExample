<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;


/**
 * Interface TreatmentAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TreatmentAPIControllerInterface
{
    function getIndividualTreatments(Request $request);
    function getLocationTreatments(Request $request);
    function createIndividualTreatments(Request $request);
    function createLocationTreatments(Request $request);
    function editIndividualTreatment(Request $request, $treatmentId);
    function editLocationTreatment(Request $request, $treatmentId);
    function deleteIndividualTreatment(Request $request, $treatmentId);
    function deleteLocationTreatment(Request $request, $treatmentId);
}