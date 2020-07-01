<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\Report\CombiFormTransportDocumentService;
use AppBundle\Service\ReportService;
use AppBundle\Util\ResultUtil;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class ReportAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/reports")
 */
class ReportAPIController extends APIController {

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
        return ResultUtil::successResult($this->get(ReportService::class)->getReports($request));
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
    return $this->get(ReportService::class)->createPedigreeCertificates($request);
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
      return $this->get(ReportService::class)->createInbreedingCoefficientsReport($request);
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
        return $this->get(ReportService::class)->createAnimalsOverviewReport($request);
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
        return $this->get(ReportService::class)->createAnnualActiveLivestockReport($request);
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
        return $this->get(ReportService::class)->createAnnualActiveLivestockRamMatesReport($request);
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
        return $this->get(ReportService::class)->createAnnualTe100UbnProductionReport($request);
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
        return $this->get(ReportService::class)->createOffspringReport($request);
    }

    /**
     * Generate ewe card report.
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
     *   description = "Generate ewe card report"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/ewe-card")
     * @Method("POST")
     */
    public function getEweCardReport(Request $request)
    {
        return $this->get(ReportService::class)->createEweCardReport($request);
    }

    /**
     * Generate animal features per year of birth report.
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
     *   description = "Generate animal features per year of birth report"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/animal-features-per-year-of-birth")
     * @Method("POST")
     */
    public function getAnimalFeaturesPerYearOfBirthReport(Request $request)
    {
        return $this->get(ReportService::class)->createAnimalFeaturesPerYearOfBirthReport($request);
    }


    /**
     * Generate animal treatments per year report.
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
     *   description = "Generate animal treatments per year report"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/animal-treatments-per-year")
     * @Method("GET")
     */
    public function getAnimalTreatmentsPerYearReport(Request $request)
    {
        return $this->get(ReportService::class)->createAnimalTreatmentsPerYearReport($request);
    }

    /**
     * Generate animal health status report.
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
     *   description = "Generate animal health status report"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/animal-health-status")
     * @Method("GET")
     */
    public function getAnimalHealthStatusReport(Request $request)
    {
        return $this->get(ReportService::class)->createAnimalHealthStatusReport($request);
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
        return $this->get(ReportService::class)->createPedigreeRegisterOverview($request);
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
     * @Method("POST")
     */
    public function getFertilizerAccountingReport(Request $request)
    {
        return $this->get(ReportService::class)->createFertilizerAccountingReport($request);
    }


    /**
     * Generate birth list report and return a download link for the pdf.
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
     *        "name"="breed_code",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="filter on breed code",
     *        "format"="?breed_code=TE100"
     *     },
     *     {
     *        "name"="pedigree_register",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="filter on pedigree register abbreviation",
     *        "format"="?pedigree_register=nts"
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
     *   description = "Generate birth list report and return a download link for the pdf"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/birth-list")
     * @Method("POST")
     */
    public function getBirthListReport(Request $request)
    {
        return $this->get(ReportService::class)->createBirthListReport($request);
    }

    /**
     * Generate company register report and return a download link for the pdf.
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
     *        "name"="sample_date",
     *        "dataType"="string",
     *        "required"=true,
     *        "description"="sample date to get animals from",
     *        "format"="?sample_date=04-10-2019"
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
     *   description = "Generate company register report and return a download link for the pdf"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/company-register")
     * @Method("POST")
     */
    public function getCompanyRegisterReport(Request $request)
    {
        return $this->get(ReportService::class)->createCompanyRegisterReport($request);
    }

    /**
     * Generate company register report and return a download link for the pdf.
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
     *        "name"="year_of_birth",
     *        "dataType"="string",
     *        "required"=true,
     *        "description"="year of birth to get weights from",
     *        "format"="?year_of_birth=2019"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate weights per year of birth report and return a download link for the csv"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/weights-per-year-of-birth")
     * @Method("POST")
     */
    public function getWeightsPerYearOfBirthReport(Request $request)
    {
        return $this->get(ReportService::class)->createWeightsPerYearOfBirthReport($request);
    }

    /**
     * Generate company register report and return a download link for the pdf.
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
     *        "name"="pedigree_register",
     *        "dataType"="string",
     *        "required"=true,
     *        "description"="filter on pedigree register abbreviation, default = empty (no pedigree register filter)",
     *        "format"="?pedigree_register=nts"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate weights per year of birth report and return a download link for the csv"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/poprep-input-file")
     * @Method("POST")
     */
    public function getPopRepReport(Request $request)
    {
        return $this->get(ReportService::class)->createPopRepReport($request);
    }

    /**
     * Generate client notes overview report and return a download link for the csv.
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
     *        "name"="company_id",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="companyId of a company. If parameter is missing then data for all companies is returned",
     *        "format"="?company_id=03379d8ae801f4c48b9497e65dcc28275c09cd0a"
     *     },
     *     {
     *        "name"="start_date",
     *        "dataType"="date",
     *        "required"=true,
     *        "description"="minimum creationDate of company notes",
     *        "format"="?start_date=2017-01-02"
     *     },
     *     {
     *        "name"="end_date",
     *        "dataType"="date",
     *        "required"=true,
     *        "description"="maximum creationDate of company notes",
     *        "format"="?end_date=2018-02-03"
     *     },
     *     {
     *        "name"="file_type",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="Only csv is allowed and is the default is this query parameter is missing",
     *        "format"="?file_type=csv"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate client notes overview report and return a download link for the csv"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/client-notes-overview")
     * @Method("POST")
     */
    public function getClientNotesOverviewReport(Request $request)
    {
        return $this->get(ReportService::class)->createClientNotesOverviewReport($request);
    }


    /**
     * Generate NSFO members and users overview report and return a download link for the csv.
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
     *        "description"="The reference date of the report, default is current date",
     *        "format"="?reference_date=2018-05-01"
     *     },
     *     {
     *        "name"="pedigree_register",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="filter on pedigree register abbreviation, default = empty (no pedigree register filter)",
     *        "format"="?pedigree_register=nts"
     *     },
     *     {
     *        "name"="must_have_animal_health_subscription",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="Only include companies that have an animal health subscription, default = false",
     *        "format"="?must_have_animal_health_subscription=true"
     *     }
     *   },
     *   resource = true,
     *   description = "Generate NSFO members and users overview report and return a download link for the csv."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/members-and-users-overview")
     * @Method("POST")
     * @throws \Exception
     */
    public function getMembersAndUsersOverviewReportService(Request $request)
    {
        return $this->get(ReportService::class)->createMembersAndUsersOverviewReportService($request);
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

    /**
     * Generate Combiform and transport document as PDF.
     *
     * ### POST EXAMPLE ###
     *
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
     *   description = "Generate Combiform and transport document as PDF."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/combi-form-transport-document")
     * @Method("POST")
     */
    public function getCombiFormAndTransportDocument(Request $request)
    {
        return $this->get(CombiFormTransportDocumentService::class)->getReport($request);
    }

    /**
     * Generate HTML output of report for easy testing in browser.
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
     *   description = "Generate HTML output of report for easy testing in browser."
     * )
     * @param Request $request the request object
     * @param string $base64encodedBody
     * @param string $reportType
     * @return JsonResponse
     * @Route("/test-template/{reportType}/{base64encodedBody}")
     * @Method("GET")
     * @throws \Exception
     */
    public function testReportTemplate(Request $request, string $reportType, string $base64encodedBody)
    {
        return $this->get(ReportService::class)->testReportTemplate($request, $reportType, $base64encodedBody);
    }
}
