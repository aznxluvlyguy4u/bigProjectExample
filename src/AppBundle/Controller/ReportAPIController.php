<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Country;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Validation\UlnValidator;
use Aws\S3\S3Client;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
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
    
    $useProductionReady = $this->getCurrentEnvironment() == 'prod';
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
            'default-header'=>true,
            'disable-smart-shrinking'=>true
    ));

    $s3Service = $this->getStorageService();
    $url = $s3Service->uploadPdf($pdfOutput, $pedigreeCertificateData->getS3Key());

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