<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

/**
 * Interface TreatmentTypeAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TreatmentTypeAPIControllerInterface
{
    function getByQuery(Request $request);
    function create(Request $request);
    function edit(Request $request, $treatmentTypeId);
    function delete(Request $request, $treatmentTypeId);
}