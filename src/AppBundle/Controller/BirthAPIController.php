<?php

namespace AppBundle\Controller;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Output\DeclareBirthResponseOutput;
use AppBundle\Util\ActionLogWriter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Export\ExportException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/births")
 */
class BirthAPIController extends APIController implements BirthAPIControllerInterface
{
    /**
     * @param Request $request the request object
     * @param String $messageNumber
     * @return JsonResponse
     * @Route("/{messageNumber}")
     * @Method("GET")
     */
    public function geBirth(Request $request, $messageNumber)
    {
        $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        $repository = $this->getDoctrine()->getRepository(Litter::class);
        $litter = $repository->findOneBy(['messageId' => $messageNumber, 'ubn' => $location->getUbn()]);

        $repository = $this->getDoctrine()->getRepository(DeclareBirth::class);
        $declarations = $repository->findOneBy(['litter' => $litter]);

        $result = DeclareBirthResponseOutput::createBirth($litter, $declarations);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("GET")
    */
    public function getHistoryBirths(Request $request)
    {
        $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT 
                    declare_nsfo_base.log_date AS log_date,
                    declare_nsfo_base.message_id AS message_id,
                    declare_nsfo_base.request_state AS request_state,
                    litter.litter_date AS date_of_birth,
                    litter.stillborn_count AS stillborn_count,
                    litter.born_alive_count AS born_alive_count,
                    litter.is_abortion AS is_abortion,
                    litter.is_pseudo_pregnancy AS is_pseudo_pregnancy,
                    litter.status AS status,
                    mother.uln_country_code AS mother_uln_country_code,
                    mother.uln_number AS mother_uln_number,
                    father.uln_country_code AS father_uln_country_code,
                    father.uln_number AS father_uln_number
                FROM declare_nsfo_base
                    INNER JOIN litter ON declare_nsfo_base.id = litter.id
                    INNER JOIN animal AS mother ON litter.animal_mother_id = mother.id
                LEFT JOIN animal AS father ON litter.animal_father_id = father.id
                WHERE (request_state <> '".RequestStateType::IMPORTED."' OR request_state <> '".RequestStateType::FAILED."') AND declare_nsfo_base.ubn = '" . $location->getUbn() ."'";

        $birthDeclarations = $em->getConnection()->query($sql)->fetchAll();
        $result = DeclareBirthResponseOutput::createHistoryResponse($birthDeclarations);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    
    /**
    * Create a new DeclareBirth request
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("POST")
    */
    public function createBirth(Request $request)
    {
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);
        $location = $this->getSelectedLocation($request);

        $requestMessages = $this->getRequestMessageBuilder()
          ->build(RequestType::DECLARE_BIRTH_ENTITY,
                  $content,
                  $client,
                  $loggedInUser,
                  $location,
                  false);

        $result = [];

        //An exception has occured, return response message
        if($requestMessages instanceof JsonResponse) {
            return $requestMessages;
        } 

        //Creating request succeeded, send to Queue
        foreach ($requestMessages as $requestMessage) {
            //First persist requestmessage, before sending it to the queue
            $this->persist($requestMessage);

            //Send it to the queue and persist/update any changed state to the database
            $result[] = $this->sendMessageObjectToQueue($requestMessage);
        }

        return new JsonResponse($result, 200);
    }

    /**
     * Revoke DeclareBirth request
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/revoke")
     * @Method("POST")
     */
    public function revokeBirth(Request $request) {
        $manager = $this->getDoctrine()->getManager();

        $location = $this->getSelectedLocation($request);
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);

        //Validate if there is a message_number.
        $validation = $this->hasMessageNumber($content);
        if(!$validation['isValid']) {
            return new JsonResponse($validation[Constant::MESSAGE_NAMESPACE], $validation[Constant::CODE_NAMESPACE]);
        }

        /** Get Litter
         * @var Litter $litter
         */
        $repository = $this->getDoctrine()->getRepository(Litter::class);
        $litter = $repository->findOneByMessageId($content['message_number']);

        foreach ($litter->getChildren() as $child) {

            /** @var Animal $child */
            if(!$child->getIsAlive()) {

                $weights = $child->getWeightMeasurements();
                foreach ($weights as $weight) {
                    $manager->remove($weight);
                }

                $tailLengths = $child->getTailLengthMeasurements();
                foreach ($tailLengths as $tailLength) {
                    $manager->remove($tailLength);
                }

                $manager->remove($child);
            }

            if($child->getIsAlive()) {
                $repository = $this->getDoctrine()->getRepository(DeclareBirthResponse::class);
                $responses = $repository->findByAnimal($child);

                foreach ($responses as $response) {
                    $message = new ArrayCollection();
                    $message->set('message_number', $response->getMessageNumber());

                    $revokeDeclarationObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $message, $client, $loggedInUser, $location);

                    $this->persist($revokeDeclarationObject);
                    $this->persistRevokingRequestState($revokeDeclarationObject->getMessageNumber());

                    $this->sendMessageObjectToQueue($revokeDeclarationObject);
                }
            }
        }
        $manager->flush();

        if(sizeof($litter->getChildren()) == 0) {
            $litter->setStatus('REVOKED');
            $litter->setRequestState(RequestStateType::REVOKED);
            $litter->setRevokeDate(new \DateTime('now'));
            $litter->setRevokedBy($loggedInUser);

            $manager->persist($litter);
            $manager->flush();

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
        }


        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }
}