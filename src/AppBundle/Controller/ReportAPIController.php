<?php

namespace AppBundle\Controller;

use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Service\ReportService;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


/**
 * Class ReportAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/reports")
 */
class ReportAPIController extends APIController {

  const IS_USE_PROD_VERSION_OUTPUT = true;

    /**
     * Get all reports
     *
     * @ApiDoc(
     *   section = "Auth",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Validate whether an accesstoken is valid or not."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("get")
     */
    public function getReports(Request $request)
    {
        return ResultUtil::successResult($this->get('AppBundle\Service\ReportService')->getReports($request));
    }

  /**
   * Generate pedigree certificates for multiple sheep and return a download link for the pdf.
   *
   * @ApiDoc(
   *   section = "Reports",
   *   headers={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "required"=true,
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *     {
   *        "name"="file_type",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="Choose file type, csv or pdf, for report output. PDF is default",
   *        "format"="?file_type=csv"
   *     },
   *     {
   *        "name"="language",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
   *        "format"="?language=en"
   *     }
   *   },
   *   resource = true,
   *   description = "Generate pedigree certificates for multiple sheep and return a download link for the pdf"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/pedigree-certificates")
   * @Method("POST")
   */
  public function getPedigreeCertificates(Request $request)
  {
    return $this->get('AppBundle\Service\ReportService')->createPedigreeCertificates($request);
  }


  /**
   * Generate inbreeding coefficient pdf report of (hypothetical) offspring of a Ram and a list of Ewes.
   *
   * @ApiDoc(
   *   section = "Reports",
   *   headers={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "required"=true,
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *     {
   *        "name"="file_type",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="Choose file type, csv or pdf, for report output. PDF is default",
   *        "format"="?file_type=csv"
   *     },
   *     {
   *        "name"="language",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
   *        "format"="?language=en"
   *     }
   *   },
   *   resource = true,
   *   description = "Generate inbreeding coefficient pdf report of (hypothetical) offspring of a Ram and a list of Ewes"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/inbreeding-coefficients")
   * @Method("POST")
   */
  public function getInbreedingCoefficientsReport(Request $request)
  {
      return $this->get('AppBundle\Service\ReportService')->createInbreedingCoefficientsReport($request);
  }


  /**
   * Generate livestock csv or pdf report.
   *
   * @ApiDoc(
   *   section = "Reports",
   *   headers={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "required"=true,
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *     {
   *        "name"="file_type",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="Choose file type, csv or pdf, for report output. PDF is default",
   *        "format"="?file_type=csv"
   *     },
   *     {
   *        "name"="language",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
   *        "format"="?language=en"
   *     },
   *     {
   *        "name"="concat_value_and_accuracy",
   *        "dataType"="boolean",
   *        "required"=false,
   *        "description"="Choose if the value and accuracy breedValue numbers should be combined into one column. false is default",
   *        "format"="?concat_value_and_accuracy=true"
   *     }
   *   },
   *   resource = true,
   *   description = "Generate livestock csv or pdf report"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/livestock")
   * @Method("POST")
   */
  public function getLiveStockReport(Request $request)
  {
      return $this->get(ReportService::class)->createLiveStockReport($request);
  }


    /**
     * Generate animals overview csv report.
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="language",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
     *        "format"="?language=en"
     *     },
     *     {
     *        "name"="concat_value_and_accuracy",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="Choose if the value and accuracy breedValue numbers should be combined into one column. false is default",
     *        "format"="?concat_value_and_accuracy=true"
     *     },
     *     {
     *        "name"="pedigree_active_end_date",
     *        "dataType"="date",
     *        "required"=false,
     *        "description"="The maximum end date of a pedigree register to be included in the returned results, default is current date",
     *        "format"="?pedigree_active_end_date=2018-01-02"
     *     },
     *     {
     *        "name"="reference_date",
     *        "dataType"="date",
     *        "required"=false,
     *        "description"="The date used to check the active ubn on reference date value, default is current date",
     *        "format"="?reference_date=2018-01-02"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate animals overview csv report"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/animals-overview")
     * @Method("GET")
     */
    public function getAnimalsOverviewReport(Request $request)
    {
        return $this->get('AppBundle\Service\ReportService')->createAnimalsOverviewReport($request);
        //return $this->get('AppBundle\Service\Report\AnimalsOverviewReportService')->getReport($request);
    }


    /**
     * Generate annual active livestock report, needed to report to RVO.
     * The reference date is set on 31 December of the given reference year.
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="language",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
     *        "format"="?language=en"
     *     },
     *     {
     *        "name"="year",
     *        "dataType"="int",
     *        "required"=true,
     *        "description"="Year of the annual report",
     *        "format"="?year=2018"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate annual active livestock report, needed to report to RVO"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/annual-active-livestock")
     * @Method("GET")
     */
    public function getAnnualActiveLivestockReport(Request $request)
    {
        return $this->get('AppBundle\Service\ReportService')->createAnnualActiveLivestockReport($request);
    }


    /**
     * Generate ram mates of annual active livestock report, needed to report to RVO.
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="language",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
     *        "format"="?language=en"
     *     },
     *     {
     *        "name"="year",
     *        "dataType"="int",
     *        "required"=true,
     *        "description"="Year of the annual report",
     *        "format"="?year=2018"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate ram mates of annual active livestock report, needed to report to RVO"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/annual-active-livestock-ram-mates")
     * @Method("GET")
     */
    public function getAnnualActiveLivestockRamMatesReport(Request $request)
    {
        return $this->get('AppBundle\Service\ReportService')->createAnnualActiveLivestockRamMatesReport($request);
    }


    /**
     * Generate annual TE100 production csv report.
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="language",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
     *        "format"="?language=en"
     *     },
     *     {
     *        "name"="year",
     *        "dataType"="int",
     *        "required"=true,
     *        "description"="Year of the annual report",
     *        "format"="?year=2018"
     *     },
     *     {
     *        "name"="end_date",
     *        "dataType"="date",
     *        "required"=false,
     *        "description"="The maximum end date of a pedigree register to be included in the returned results, default is current dateTime",
     *        "format"="?end_date=2018-01-02"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate annual TE100 production csv report"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/annual-te100-ubn-production")
     * @Method("GET")
     */
    public function getAnnualTe100ProductionReport(Request $request)
    {
        return $this->get('AppBundle\Service\ReportService')->createAnnualTe100UbnProductionReport($request);
    }


    /**
     * Generate offspring report.
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="language",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose language option for column headers: en (english) or nl (dutch). nl is default",
     *        "format"="?language=en"
     *     },
     *     {
     *        "name"="concat_value_and_accuracy",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="Choose if the value and accuracy breedValue numbers should be combined into one column. false is default",
     *        "format"="?concat_value_and_accuracy=true"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate offspring report"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/offspring")
     * @Method("POST")
     */
    public function getOffspringReport(Request $request)
    {
        return $this->get('AppBundle\Service\ReportService')->createOffspringReport($request);
    }


    /**
     * Generate pedigree register xls report by abbreviation in query parameter 'type'
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="file_type",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose file type, csv or pdf, for report output. PDF is default",
     *        "format"="?file_type=csv"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate pedigree register xls report by abbreviation in query parameter 'type'"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/excel/pedigreeregister")
     * @Method("GET")
     */
    public function getPedigreeRegisterOverview(Request $request)
    {
        return $this->get('AppBundle\Service\ReportService')->createPedigreeRegisterOverview($request);
    }


    /**
     * Generate breed index and breed value overview report by 'file_type' xls/csv.
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="file_type",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose file type, csv or pdf, for report output. PDF is default",
     *        "format"="?file_type=csv"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate breed index and breed value overview report by 'file_type' xls/csv."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/excel/breed-values-overview")
     * @Method("GET")
     */
    public function getBreedValuesReportOverview(Request $request)
    {
        return $this->get('AppBundle\Service\Report\BreedValuesOverviewReportService')->request($request, $this->getEmployee());
    }


    /**
     * Generate fertilizer accounting report by 'file_type' xls/csv.
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="reference_date",
     *        "dataType"="date",
     *        "required"=false,
     *        "description"="The date of the last month included in the report, default is current date",
     *        "format"="?reference_date=2018-05-01"
     *     },
     *     {
     *        "name"="file_type",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose file type, csv or pdf, for report output. PDF is default",
     *        "format"="?file_type=csv"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate fertilizer accounting report by 'file_type' xls/csv."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/fertilizer-accounting")
     * @Method("GET")
     */
    public function getFertilizerAccountingReport(Request $request)
    {
        return $this->get('AppBundle\Service\ReportService')->createFertilizerAccountingReport($request);
    }


    /**
     * Generate VWA animal details report as pdf.
     *
     * ### POST EXAMPLE ###
     *
     *  {
     *      "result": {
     *          "locations": [
     *              {
     *                  "ubn": "1674459"
     *              },
     *              {
     *                  "ubn": "1245656"
     *              }
     *          ],
     *          "animals": [
     *              {
     *                  "uln_country_code": "NL",
     *                  "uln_number": "10083749930"
     *              },
     *              {
     *                  "uln_country_code": "NL",
     *                  "uln_number": "10083749990"
     *              }
     *          ]
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate VWA animal details report as pdf."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/vwa/animal-details")
     * @Method("POST")
     */
    public function getVwaAnimalDetailsReport(Request $request)
    {
        return $this->get('app.report.vwa.animal_details')->getAnimalDetailsReport($request);
    }


    /**
     * Generate VWA UBNs overview report as pdf or csv.
     *
     * ### POST EXAMPLE ###
     *
     *  {
     *      "result": {
     *          "locations": [
     *              {
     *                  "ubn": "1674459"
     *              },
     *              {
     *                  "ubn": "1245656"
     *              }
     *          ]
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Reports",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *     {
     *        "name"="file_type",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Choose file type, csv or pdf, for report output. PDF is default",
     *        "format"="?file_type=csv"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate VWA UBNs overview report as pdf or csv."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/vwa/ubns-overview")
     * @Method("POST")
     */
    public function getUbnsOverviewReport(Request $request)
    {
        return $this->get('app.report.vwa.ubns_overview')->getUbnsOverviewReport($request);
    }
}