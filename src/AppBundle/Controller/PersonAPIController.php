<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Entity\Person;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\TokenType;
use AppBundle\Validation\EmployeeValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/persons")
 */
class PersonAPIController extends APIController {

  /**
   * Generate new personIds ('apiKeys') for all persons that don't have one yet.
   *
   * @Route("/generate-person-ids")
   * @Method("POST")
   */
  public function generateNewPersonIds(Request $request)
  {
    //User must be an Employee and not a Client
    $employee = $this->getAuthenticatedEmployee($request);
    $employeeValidation = new EmployeeValidator($employee);
    if(!$employeeValidation->getIsValid()) {
      return $employeeValidation->createJsonErrorResponse();
    }

    $persons = $this->getDoctrine()->getRepository(Person::class)->findAll();

    foreach ($persons as $person) {
      if($person->getPersonId() == null || $person->getPersonId() == "") {
        $person->setPersonId(Utils::generatePersonId());
        $this->getDoctrine()->getEntityManager()->persist($person);
        $this->getDoctrine()->getEntityManager()->flush();
      }
    }

    return new JsonResponse("ok", 200);
  }
}