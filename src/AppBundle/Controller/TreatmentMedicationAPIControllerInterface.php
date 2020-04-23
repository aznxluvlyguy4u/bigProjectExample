<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

/**
 * Interface TreatmentMedicationAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TreatmentMedicationAPIControllerInterface
{
    function getByQuery(Request $request);
    function create(Request $request);
    function edit(Request $request, $treatmentMedicationId);
    function delete(Request $request, $treatmentMedicationId);
}