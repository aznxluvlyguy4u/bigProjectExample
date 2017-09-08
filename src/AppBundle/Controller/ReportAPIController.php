<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Constant\Constant;
use AppBundle\Constant\Environment;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Country;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\WorkerTaskType;
use AppBundle\Output\Output;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Report\LivestockReportData;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Report\ReportBase;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\TwigOutputUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\InbreedingCoefficientInputValidator;
use AppBundle\Validation\UlnValidator;
use AppBundle\Worker\Task\WorkerMessageBody;
use Aws\S3\S3Client;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class ReportAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/reports")
 */
class ReportAPIController extends APIController {

  const IS_LOCAL_TESTING = false; //To save the generated files locally instead of in the S3 Bucket.
  const DISPLAY_PDF_AS_HTML = false;
  const IS_USE_PROD_VERSION_OUTPUT = true;

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
    return $this->get('app.report.pedigree_certificates')->getReport($request);
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
      return $this->get('app.report.inbreeding_coefficient')->getReport($request);
  }


  /**
   * Generate livestock pdf report.
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
   *   description = "Generate livestock pdf report"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/livestock")
   * @Method("POST")
   */
  public function getLiveStockReport(Request $request)
  {
      return $this->get('app.report.livestock')->getReport($request);
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
        return $this->get('app.report.pedigree_register')->request($request);
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
        return $this->get('app.report.breed_values_overview')->request($request, $this->getEmployee());
    }


    /**
     * Generate VWA animal details report by 'file_type' pdf/csv.
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
     * @Route("/vwa/animal-details")
     * @Method("POST")
     */
    public function getVwaAnimalDetailsReport(Request $request)
    {
        return $this->get('app.report.vwa.animal_details')->getAnimalDetailsReport($request);
    }


}