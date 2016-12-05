<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Constant\Constant;
use AppBundle\Constant\Environment;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Country;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Report\LivestockReportData;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Report\ReportBase;
use AppBundle\Util\TwigOutputUtil;
use AppBundle\Validation\InbreedingCoefficientInputValidator;
use AppBundle\Validation\UlnValidator;
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
  const IS_USE_PROD_VERSION_OUTPUT = true;

  /**
   * Generate pedigree certificates for multiple sheep and return a download link for the pdf.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Generate pedigree certificates for multiple sheep and return a download link for the pdf",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/pedigree-certificates")
   * @Method("POST")
   */
  public function getPedigreeCertificates(Request $request) {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);
    $content = $this->getContentAsArray($request);
    $em = $this->getDoctrine()->getManager();

    //Validate if given ULNs are correct AND there should at least be one ULN given
    $ulnValidator = new UlnValidator($em, $content, true, null, $location);
    if(!$ulnValidator->getIsUlnSetValid()) {
      return $ulnValidator->createArrivalJsonErrorResponse();
    }

    //Or use... $this->getCurrentEnvironment() == Environment::PROD;
    if(self::IS_USE_PROD_VERSION_OUTPUT) {
      $twigFile = 'Report/pedigree_certificates.html.twig';
    } else {
      //containing extra unfinished features
      $twigFile = 'Report/pedigree_certificates_beta.html.twig';
    }

    $pedigreeCertificateData = new PedigreeCertificates($em, $content, $client, $location);
    $variables = $pedigreeCertificateData->getReports();
    $html = $this->renderView($twigFile, ['variables' => $variables]);

    if(self::IS_LOCAL_TESTING) {
      //Save pdf in local cache
      return new JsonResponse([Constant::RESULT_NAMESPACE => $this->saveFileLocally($pedigreeCertificateData, $html, TwigOutputUtil::pdfLandscapeOptions())], 200);
    }

    $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,TwigOutputUtil::pdfLandscapeOptions());

    $s3Service = $this->getStorageService();
    $url = $s3Service->uploadPdf($pdfOutput, $pedigreeCertificateData->getS3Key());

    return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
  }


  /**
   * Generate inbreeding coefficient pdf report of (hypothetical) offspring of a Ram and a list of Ewes.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Generate inbreeding coefficient pdf report of (hypothetical) offspring of a Ram and a list of Ewes",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/inbreeding-coefficients")
   * @Method("POST")
   */
  public function getInbreedingCoefficientsReport(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);
    $content = $this->getContentAsArray($request);
    $em = $this->getDoctrine()->getManager();

    $inbreedingCoefficientInputValidator = new InbreedingCoefficientInputValidator($em, $content, $client);
    if(!$inbreedingCoefficientInputValidator->getIsInputValid()) {
      return $inbreedingCoefficientInputValidator->createJsonResponse();
    }

    $reportResults = new InbreedingCoefficientReportData($em, $content, $client);
    $reportData = $reportResults->getData();
    $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

    $twigFile = 'Report/inbreeding_coefficient_report.html.twig';
    $html = $this->renderView($twigFile, ['variables' => $reportData]);

    if(self::IS_LOCAL_TESTING) {
      //Save pdf in local cache
      return new JsonResponse([Constant::RESULT_NAMESPACE => $this->saveFileLocally($reportResults, $html, TwigOutputUtil::pdfPortraitOptions())], 200);
    }

    $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,TwigOutputUtil::pdfPortraitOptions());
    
    $s3Service = $this->getStorageService();
    $url = $s3Service->uploadPdf($pdfOutput, $reportResults->getS3Key());

    return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
  }


  /**
   * Generate livestock pdf report.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Generate livestock pdf report",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/livestock")
   * @Method("POST")
   */
  public function getLiveStockReport(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);
    //TODO read options from the content it the Array. Deactivated, so the front-end doesn't even need to send an empty array
//    $content = $this->getContentAsArray($request);
    $content = new ArrayCollection(); //Just a placeholder for the array holding the options
    
    /** @var ObjectManager $em */
    $em = $this->getDoctrine()->getManager();

    //TODO add validation for options, when adding the options

    $reportResults = new LivestockReportData($em, $content, $client, $location);
    $reportData = $reportResults->getData();
    $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

    $twigFile = 'Report/livestock_report.html.twig';
    $html = $this->renderView($twigFile, ['variables' => $reportData]);

    if(self::IS_LOCAL_TESTING) {
      //Save pdf in local cache
      return new JsonResponse([Constant::RESULT_NAMESPACE => $this->saveFileLocally($reportResults, $html, TwigOutputUtil::pdfLandscapeOptions())], 200);
    }

    $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,TwigOutputUtil::pdfLandscapeOptions());

    $s3Service = $this->getStorageService();
    $url = $s3Service->uploadPdf($pdfOutput, $reportResults->getS3Key());

    return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
  }


  /**
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/testdata")
   * @Method("POST")
   */
  public function test(Request $request)
  {

  }


  /**
   * @param ReportBase $report
   * @param $html
   * @param array $pdfOptions
   * @return string
   */
  private function saveFileLocally(ReportBase $report, $html, $pdfOptions = null)
  {
    $folderPath = $this->getParameter('kernel.cache_dir');
    $generatedPdfPath = $report->getFilePath($folderPath);
    $this->get('knp_snappy.pdf')->generateFromHtml($html, $generatedPdfPath, $pdfOptions);
    return $generatedPdfPath;
  }
}