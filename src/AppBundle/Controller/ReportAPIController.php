<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Country;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Validation\UlnValidator;
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
    $em = $this->getDoctrine()->getEntityManager();

    //Validate if given ULNs are correct AND there should at least be one ULN given
    $ulnValidator = new UlnValidator($em, $content, true, $client);
    if(!$ulnValidator->getIsUlnSetValid()) {
      return $ulnValidator->createArrivalJsonErrorResponse();
    }

    //TODO TEST Retrieve the Animals and bloodline/pedigree

    //TODO Prettify pdf document from twig view

    $pedigreeCertificateData = new PedigreeCertificates($em, $content, $client, $location);
    $generatedPdfPath = $pedigreeCertificateData->getFilePath($this->getParameter('report_directory_path'));
    $variables = $pedigreeCertificateData->getReports();

    $html = $this->renderView('Report/pedigree_certificates.html.twig', ['variables' => $variables]);
    $this->get('knp_snappy.pdf')->generateFromHtml($html, $generatedPdfPath);

    //TODO Save the report link, delete the file on S3 each time a new report is requested and overwrite the saved report link

    $result = 'success!'; //TODO return the S3 download link

    return new JsonResponse([Constant::RESULT_NAMESPACE => $result], 200);
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