<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/companies")
 */
class CompanyAPIController extends APIController
{
    const DEFAULT_COUNTRY = '';

    /**
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getCompanies(Request $request)
    {
        return $this->get('app.company')->getCompanies($request);
    }

    /**
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createCompany(Request $request)
    {
        return $this->get('app.company')->createCompany($request);
    }

    /**
     * @param Request $request   the request object
     * @param string $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}")
     * @Method("GET")
     */
    public function getCompany(Request $request, $companyId)
    {
        return $this->get('app.company')->getCompany($request, $companyId);
    }

    /**
     * @param string $companyId
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("/{companyId}")
     * @Method("PUT")
     */
    public function UpdateCompany(Request $request, $companyId)
    {
        return $this->get('app.company')->updateCompany($request, $companyId);
    }

    /**
     * @param Request $request   the request object
     * @param string $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/status")
     * @Method("PUT")
     */
    public function setCompanyInactive(Request $request, $companyId)
    {
        return $this->get('app.company')->setCompanyInactive($request, $companyId);
    }

    /**
     * @param Request $request   the request object
     * @param string $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/details")
     * @Method("GET")
     */
    public function getCompanyDetails(Request $request, $companyId)
    {
        return $this->get('app.company')->getCompanyDetails($request, $companyId);
    }

    /**
     * @param Request $request   the request object
     * @param string $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/notes")
     * @Method("GET")
     */
    public function getCompanyNotes(Request $request, $companyId)
    {
        return $this->get('app.company')->getCompanyNotes($request, $companyId);
    }

    /**
     * @param Request $request   the request object
     * @param string $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/notes")
     * @Method("POST")
     */
    public function createCompanyNotes(Request $request, $companyId)
    {
        return $this->get('app.company')->createCompanyNotes($request, $companyId);
    }

    /**
     * @return JsonResponse
     * @Route("/invoice/info")
     * @Method("GET")
     */
    public function getCompanyInvoiceDetails(){
        return $this->get('app.company')->getCompanyInvoiceDetails();
    }

    /**
     * @param Request $request   the request object
     * @return JsonResponse
     * @Route("/company/name")
     * @Method("GET")
     */
    public function getCompaniesByName(Request $request){
        return $this->get('app.company')->getCompaniesByName($request);
    }
}
