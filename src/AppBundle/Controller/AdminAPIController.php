<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\TokenType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/admins")
 */
class AdminAPIController extends APIController {

  const timeLimitInMinutes = 3;

  /**
   *
   * Get ghost accesstoken to 
   *
   * @Route("/ghost")
   * @Method("POST")
   */
  public function getTemporaryGhostToken(Request $request) {

    $employee = $this->getAuthenticatedEmployee($request);
    if($employee == null) {
      return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
    }

    $content = $this->getContentAsArray($request);
    $companyId = $content->get(JsonInputConstant::COMPANY_ID);

    $company = $this->getDoctrine()->getRepository(Company::class)->find($companyId);
    $client = $company->getOwner();

    $existingGhostToken = $this->getDoctrine()->getRepository(Token::class)->findOneBy(array('owner' => $client, 'admin' => $employee));
    if($existingGhostToken != null) {
      $this->getDoctrine()->getEntityManager()->remove($existingGhostToken);
    }

    $ghostToken = new Token(TokenType::GHOST);
    $ghostToken->setOwner($client);
    $ghostToken->setAdmin($employee);
    $employee->addToken($ghostToken);
    $client->addToken($ghostToken);

    $this->getDoctrine()->getEntityManager()->persist($client);
    $this->getDoctrine()->getEntityManager()->flush();

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $ghostToken->getCode()), 200);
  }

  /**
   *
   * Verify ghost token.
   *
   * @Route("/verify-ghost-token")
   * @Method("PUT")
   */
  public function verifyGhostToken(Request $request) {

    if ($request->headers->has(Constant::GHOST_TOKEN_HEADER_NAMESPACE)) {
      $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);

      if ($ghostTokenCode != null){
        $ghostToken = $this->getDoctrine()->getRepository(Token::class)->findOneBy(array('code' => $ghostTokenCode));
        if ($ghostToken != null) {

          //First verify if ghostToken has already been verified or not
          if($ghostToken->getIsVerified()) {
            $message = 'GHOST TOKEN HAS ALREADY BEEN VERIFIED';
          } else {
            $now = new \DateTime();
            $timeExpiredInMinutes = ($now->getTimestamp() - $ghostToken->getCreationDateTime()->getTimeStamp())/60;
            $isGhostTokenExpired = $timeExpiredInMinutes > self::timeLimitInMinutes;

            if ($isGhostTokenExpired){
              $this->getDoctrine()->getEntityManager()->remove($ghostToken);
              $message = 'GHOST TOKEN EXPIRED AND WAS DELETED. VERIFY GHOST TOKENS WITHIN 3 MINUTES';

            } else { //not expired
              $ghostToken->setIsVerified(true);
              $this->getDoctrine()->getEntityManager()->persist($ghostToken);
              $message = 'GHOST TOKEN IS VERIFIED';
            }
            $this->getDoctrine()->getEntityManager()->flush();
          }

        } else {
          $message = 'NO GHOST TOKEN FOUND FOR GIVEN CODE';
        }
      } else {
        $message = 'GHOST TOKEN FIELD IS EMPTY';
      }
    } else {
      $message = 'GHOST TOKEN HEADER MISSING';
    }

    return new JsonResponse([Constant::RESULT_NAMESPACE => $message], 200);
  }

  /**
   * Migrate accessTokens from string field to array field.
   *
   * @Route("/migrate")
   * @Method("POST")
   */
  public function migrateTokensToArray(Request $request) {
    $persons = $this->getDoctrine()->getRepository(Person::class)->findAll();

    foreach ($persons as $person) {
      if(sizeof($person->getTokens()) == 0) {
        $token = new Token(TokenType::ACCESS, $person->getAccessToken());
        $person->addToken($token);
        $token->setOwner($person);
        $this->getDoctrine()->getEntityManager()->persist($person);
        $this->getDoctrine()->getEntityManager()->flush();
      }
    }
    
    return new JsonResponse("ok", 200);
  }
}