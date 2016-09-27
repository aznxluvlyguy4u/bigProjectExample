<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Constant\Constant;
use AppBundle\Constant\Environment;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Country;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Validation\InbreedingCoefficientInputValidator;
use AppBundle\Validation\UlnValidator;
use Aws\S3\S3Client;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
    $ulnValidator = new UlnValidator($em, $content, true, $client);
    if(!$ulnValidator->getIsUlnSetValid()) {
      return $ulnValidator->createArrivalJsonErrorResponse();
    }
    
    $useProductionReady = true;// $this->getCurrentEnvironment() == Environment::PROD;
    if($useProductionReady) {
      $twigFile = 'Report/pedigree_certificates.html.twig';
    } else {
      //containing extra unfinished features
      $twigFile = 'Report/pedigree_certificates_beta.html.twig';
    }

    $pedigreeCertificateData = new PedigreeCertificates($em, $content, $client, $location);
    $folderPath = $this->getParameter('kernel.cache_dir');
    $generatedPdfPath = $pedigreeCertificateData->getFilePath($folderPath);
    $variables = $pedigreeCertificateData->getReports();
    $html = $this->renderView($twigFile, ['variables' => $variables]);
    $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,
        array(
            'orientation'=>'Landscape',
            'default-header'=>false,
            'disable-smart-shrinking'=>true,
            'print-media-type' => true,
            'margin-top'    => 6,
            'margin-right'  => 8,
            'margin-bottom' => 4,
            'margin-left'   => 8,
    ));

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

    $useProductionReady = $this->getCurrentEnvironment() == Environment::PROD;
    if($useProductionReady) {
      $reportData[ReportLabel::IS_PROD_ENV] = true;
    } else {
      $reportData[ReportLabel::IS_PROD_ENV] = false;
    }

    $twigFile = 'Report/inbreeding_coefficient_report.html.twig';
    $html = $this->renderView($twigFile, ['variables' => $reportData]);
    $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,
        array(
            'orientation'=>'Portrait',
            'default-header'=>false,
            'disable-smart-shrinking'=>true,
            'print-media-type' => true,
            'margin-top'    => 6,
            'margin-right'  => 8,
            'margin-bottom' => 4,
            'margin-left'   => 8,
        ));
    
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
}