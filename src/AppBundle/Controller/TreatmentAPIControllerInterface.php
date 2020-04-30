<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;


/**
 * Interface TreatmentAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TreatmentAPIControllerInterface
{
//    function getIndividualTreatments(Request $request);
//    function getLocationTreatments(Request $request);
    function createIndividualTreatment(Request $request);
    function createLocationTreatment(Request $request);
    function editTreatment(Request $request, $treatmentId);
    function deleteIndividualTreatment(Request $request, $treatmentId);
    function deleteLocationTreatment(Request $request, $treatmentId);
}