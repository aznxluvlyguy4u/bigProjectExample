<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\CompanyNote;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\CompanyRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Output\CompanyNoteOutput;
use AppBundle\Output\CompanyOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\CompanyValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Doctrine\ORM\Query;

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
     * @param Company $companyId
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
     * @var Company $company
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
     * @param Company $companyId
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
     * @param String  $companyId
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
     * @param String  $companyId
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
     * @param String  $companyId
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
