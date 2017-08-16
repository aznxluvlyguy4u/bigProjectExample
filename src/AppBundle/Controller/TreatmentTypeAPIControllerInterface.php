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
}