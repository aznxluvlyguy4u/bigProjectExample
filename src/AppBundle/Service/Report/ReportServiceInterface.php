<?php


namespace AppBundle\Service\Report;


use Symfony\Component\HttpFoundation\Request;

interface ReportServiceInterface
{

    /**
     * @param Request $request
     * @return mixed
     */
    function getReport(Request $request);
}