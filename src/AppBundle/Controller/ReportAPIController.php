<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Validation\UlnValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
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
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\AnimalRepository")
   * @Method("POST")
   */
  public function getPedigreeCertificates(Request $request) {
    $client = $this->getAuthenticatedUser($request);
    $content = $this->getContentAsArray($request);
    $em = $this->getDoctrine()->getEntityManager();
      
    $ulnValidator = new UlnValidator($em, $content, true, $client);
    if(!$ulnValidator->getIsUlnSetValid()) {
      return $ulnValidator->createArrivalJsonErrorResponse();
    }

    $result = 'success!';

    //TODO Retrieve the Animals and bloodline/pedigree

    //TODO Generate pdf document from twig view
    //https://github.com/KnpLabs/KnpSnappyBundle#generate-a-pdf-document-from-a-twig-view
//    $this->get('knp_snappy.pdf')->generateFromHtml(
//        $this->renderView(
//            'MyBundle:Foo:bar.html.twig',
//            array(
//                'some'  => $vars
//            )
//        ),
//        '/path/to/the/file.pdf'
//    );

    return new JsonResponse($result, 200);
  }
}