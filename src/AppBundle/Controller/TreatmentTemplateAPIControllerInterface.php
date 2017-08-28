<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;


/**
 * Interface TreatmentTemplateAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TreatmentTemplateAPIControllerInterface
{
    function getIndividualDefaultTemplates(Request $request);
    function getIndividualSpecificTemplates(Request $request, $ubn);
    function getLocationDefaultTemplates(Request $request);
    function getLocationSpecificTemplates(Request $request, $ubn);
    function createIndividualTemplate(Request $request);
    function createLocationTemplate(Request $request);
    function editIndividualTemplate(Request $request, $templateId);
    function editLocationTemplate(Request $request, $templateId);
    function deleteIndividualTemplate(Request $request, $templateId);
    function deleteLocationTemplate(Request $request, $templateId);
}