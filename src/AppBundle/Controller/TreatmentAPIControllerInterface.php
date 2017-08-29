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
    function editIndividualTreatments(Request $request, $treatmentId);
    function editLocationTreatments(Request $request, $treatmentId);
    function deleteIndividualTreatments(Request $request, $treatmentId);
    function deleteLocationTreatments(Request $request, $treatmentId);
}