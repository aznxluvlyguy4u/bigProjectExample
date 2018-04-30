<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface ReportServiceInterface
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getReport(Request $request);
}