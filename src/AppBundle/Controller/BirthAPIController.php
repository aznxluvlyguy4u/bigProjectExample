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
use AppBundle\Entity\Tag;
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
     * @param String $litterId
     * @return JsonResponse
     * @Route("/{litterId}")
     * @Method("GET")
     */
    public function getBirth(Request $request, $litterId)
    {
        $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        $repository = $this->getDoctrine()->getRepository(Litter::class);
        $litter = $repository->findOneBy(['id' => $litterId, 'ubn' => $location->getUbn()]);

        $result = DeclareBirthResponseOutput::createBirth($litter, $litter->getDeclareBirths());

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
                    declare_nsfo_base.id AS id,
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
        $statusCode = 428;
        $litterId = null;

        if (!key_exists('litter_id', $content->toArray())) {
            return new JsonResponse(
              array (
                Constant::RESULT_NAMESPACE => array (
                  'code' => $statusCode,
                  "message" => "Mandatory Litter Id not given.",
                )
              ), $statusCode);
        }

        $litterId = $content['litter_id'];
        $repository = $this->getDoctrine()->getRepository(Litter::class);
        $litter = $repository->findOneBy(array ('id' => $litterId));

        if (!$litter) {
            return new JsonResponse(
              array (
                Constant::RESULT_NAMESPACE => array (
                  'code' => $statusCode,
                  "message" => "No litter was not found.",
                )
              ), $statusCode);
        }

        //Create revoke request for every declareBirth request
        if ($litter->getDeclareBirths()->count() > 0) {
            foreach ($litter->getDeclareBirths() as $declareBirth) {
                $declareBirthResponse = $this->getEntityGetter()
                  ->getResponseDeclarationByMessageId($declareBirth->getMessageId());

                if ($declareBirthResponse) {
                    $message = new ArrayCollection();
                    $message->set(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE, $declareBirthResponse->getMessageNumber());

                    $revokeDeclarationObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $message, $client, $loggedInUser, $location);
                    $this->persist($revokeDeclarationObject);
                    $this->persistRevokingRequestState($revokeDeclarationObject->getMessageNumber());

                    $this->sendMessageObjectToQueue($revokeDeclarationObject);
                }
            }
        }

        //Remove still born childs
        foreach ($litter->getStillborns() as $stillborn) {
            $manager->remove($stillborn);
        }

        //Remove alive child animal
        foreach ($litter->getChildren() as $child) {

            //Remove animal residence
            $residenceHistory = $child->getAnimalResidenceHistory();
            foreach ($residenceHistory as $residence) {
                $manager->remove($residence);
            }

            //Remove weights
            $weights = $child->getWeightMeasurements();
            foreach ($weights as $weight) {
                $manager->remove($weight);
            }

            //Remove tail lengths
            $tailLengths = $child->getTailLengthMeasurements();
            foreach ($tailLengths as $tailLength) {
                $manager->remove($tailLength);
            }

            //Remove bodyfats
            $bodyFats = $child->getBodyFatMeasurements();
            foreach ($bodyFats as $bodyFat) {
                $manager->remove($bodyFat);
            }

            //Remove exteriors
            $exteriors = $child->getExteriorMeasurements();
            foreach ($exteriors as $exterior) {
                $manager->remove($exterior);
            }

            //Remove muscleThickness
            $muscleThicknesses = $child->getMuscleThicknessMeasurements();
            foreach ($muscleThicknesses as $muscleThickness) {
                $manager->remove($muscleThickness);
            }

            //Remove breedCodes
            if ($child->getBreedCodes()) {
                $breedCodes = $child->getBreedCodes();

                foreach ($breedCodes->getCodes() as $codes) {
                    $manager->remove($codes);
                }
                $child->setBreedCodes(null);
                $manager->remove($breedCodes);
            }

            //Remove breedset values
            $breedValues = $child->getBreedValuesSets();
            foreach ($breedValues as $breedValue) {
                $manager->remove($breedValue);
            }

            $manager->flush();

            //Restore tag if it does not exist
            $tagToRestore = null;
            $tagToRestore = $manager->getRepository(Tag::getClassName())
              ->findByUlnNumberAndCountryCode($child->getUlnCountryCode(), $child->getUlnNumber());

            if ($tagToRestore) {
                $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
            }
            else {
                $tagToRestore = new Tag();
                $tagToRestore->setLocation($location);
                $tagToRestore->setOrderDate(new \DateTime());
                $tagToRestore->setOwner($client);
                $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
                $tagToRestore->setUlnCountryCode($child->getUlnCountryCode());
                $tagToRestore->setUlnNumber($child->getUlnNumber());
                $tagToRestore->setAnimalOrderNumber($child->getAnimalOrderNumber());
            }

            $manager->persist($tagToRestore);

            //Remove child from location
            if ($location->getAnimals()->contains($child)) {
                $location->getAnimals()->removeElement($child);
                $manager->persist($location);
            }

            $child->setLitter(null);
            $manager->persist($child);
            $manager->flush();

            $litter->removeChild($child);
            $litter->getChildren()->removeElement($child);
            $declareBirths = $litter->getDeclareBirths();

            foreach ($declareBirths as $declareBirth) {

                if ($declareBirth->getAnimal() != null) {
                    if ($declareBirth->getAnimal()->getUlnNumber() == $child->getUlnNumber()) {
                        $declareBirthResponses = $declareBirth->getResponses();
                        $declareBirth->setRequestState(RequestStateType::REVOKED);

                        foreach ($declareBirthResponses as $declareBirthResponse) {
                            if($declareBirthResponse->getAnimal() != null) {
                                if ($declareBirthResponse->getAnimal()->getUlnNumber() == $child->getUlnNumber()) {
                                    $declareBirthResponse->setAnimal(null);
                                    $manager->persist($declareBirthResponse);

                                }
                            }
                        }
                        //Remove child animal
                        $declareBirth->setAnimal(null);
                        $manager->persist($declareBirth);
                    }
                }
            }

            //Remove child animal
            $manager->remove($child);
        }

        $manager->flush();

        //Re-retrieve litter, check count
        $litter = $repository->findOneBy(array ('id'=> $litterId));

        if($litter->getChildren()->count() == 0) {
            $litter->setStatus('REVOKED');
            $litter->setRequestState(RequestStateType::REVOKED);
            $litter->setRevokeDate(new \DateTime());
            $litter->setRevokedBy($loggedInUser);

            $manager->persist($litter);
            $manager->flush();

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $litter), 200);
        }

        return new JsonResponse(
          array(
            Constant::RESULT_NAMESPACE => array (
              'code' => $statusCode,
              "message" => "Failed to revoke and remove all child animals ",
            )
          ), $statusCode);
    }
}