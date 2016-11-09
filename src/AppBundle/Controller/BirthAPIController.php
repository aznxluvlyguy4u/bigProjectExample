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
     * @Route("/false-birth")
     * @Method("POST")
     */
    public function createFalseBirth(Request $request) {
        $manager = $this->getDoctrine()->getManager();
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);

        $location = $this->getSelectedLocation($request);
        $content = $this->getContentAsArray($request);
        $company = $location->getCompany();

        // Litter
        $litter = new Litter();
        $litter->setLitterDate(new \DateTime($content['date_of_birth']));
        $litter->setIsAbortion($content['is_aborted']);
        $litter->setIsPseudoPregnancy($content['is_pseudo_pregnancy']);
        $litter->setStillbornCount(0);
        $litter->setBornAliveCount(0);
        $litter->setStatus('COMPLETE');

        $litter->setRequestState(RequestStateType::FINISHED);
        $litter->setActionBy($loggedInUser);
        $litter->setRelationNumberKeeper($company->getOwner()->getRelationNumberKeeper());
        $litter->setUbn($location->getUbn());
        $litter->setIsHidden(false);
        $litter->setIsOverwrittenVersion(false);
        $litter->setMessageId(MessageBuilderBase::getNewRequestId());

        // Mother
        /** @var Ewe $mother */
        $repository = $this->getDoctrine()->getRepository(Constant::EWE_REPOSITORY);
        $contentMother = $content['mother'];

        if(key_exists('uln_country_code', $contentMother) && key_exists('uln_number', $contentMother)) {
            if ($contentMother['uln_country_code'] != '' && $contentMother['uln_number'] != '') {
                $mother = $repository->findOneBy([
                    'ulnCountryCode' => $contentMother['uln_country_code'],
                    'ulnNumber' => $contentMother['uln_number'],
                    'isAlive' => true
                ]);

                if ($mother == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE MOTHER IS NOT FOUND'
                    ], 428);
                }
            }
        }

        $motherCompany = $mother->getLocation()->getCompany();
        if($company != $motherCompany) {
            return new JsonResponse([
                Constant::CODE_NAMESPACE => 428,
                Constant::MESSAGE_NAMESPACE => 'THE MOTHER IS NOT IN YOUR LIVESTOCK'
            ], 428);
        }

        /** @var Litter $litter */
        foreach($mother->getLitters() as $motherLitter) {
            $litterDate = $motherLitter->getLitterDate()->format('Y-m-d');
            $contentDate = (new \DateTime($content['date_of_birth']))->format('Y-m-d');

            if(($litterDate == $contentDate) && ($motherLitter->getStatus() == 'COMPLETED' || $motherLitter->getStatus() == 'OPEN')) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE MOTHER ALREADY HAS A LITTER REGISTERED ON THIS DATE'
                ], 428);
            }
        }

        $litter->setAnimalMother($mother);

        // Father
        $repository = $this->getDoctrine()->getRepository(Constant::RAM_REPOSITORY);
        $contentFather = $content['father'];

        $father = null;

        if(key_exists('pedigree_country_code', $contentFather) && key_exists('pedigree_number', $contentFather)) {
            if($contentFather['pedigree_country_code'] != '' && $contentFather['pedigree_number'] != '') {
                $father = $repository->findOneBy([
                    'pedigreeCountryCode' => $contentFather['pedigree_country_code'],
                    'pedigreeNumber' => $contentFather['pedigree_number'],
                    'isAlive' => true
                ]);

                if($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE PEDIGREE OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        if(key_exists('uln_country_code', $contentFather) && key_exists('uln_number', $contentFather)) {
            if ($contentFather['uln_country_code'] != '' && $contentFather['uln_number'] != '') {
                $father = $repository->findOneBy([
                    'ulnCountryCode' => $contentFather['uln_country_code'],
                    'ulnNumber' => $contentFather['uln_number'],
                    'isAlive' => true
                ]);

                if ($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        // Persist Litter
        $this->getDoctrine()->getManager()->persist($litter);
        $this->getDoctrine()->getManager()->flush();

        // Action Log
        // DON'T PUT IN ABOVE OTHER CODE.. IT WILL MESS UP PERSIST SEQUENCE
        $log = ActionLogWriter::createFalseBirth($manager, $client, $loggedInUser, $mother);

        // Complete Action Log
        ActionLogWriter::completeActionLog($manager, $log);

        return new JsonResponse([Constant::RESULT_NAMESPACE => 'ok'], 200);
    }

    /**
    * Create a new DeclareBirth request
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("POST")
    */
    public function createBirth(Request $request) {
        $manager = $this->getDoctrine()->getManager();

        $location = $this->getSelectedLocation($request);
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);

        // Company
        $company = $location->getCompany();

        // Litter
        $litter = new Litter();
        $litter->setLitterDate(new \DateTime($content['date_of_birth']));
        $litter->setIsAbortion($content['is_aborted']);
        $litter->setIsPseudoPregnancy($content['is_pseudo_pregnancy']);
        $litter->setStatus('INCOMPLETE');

        $litter->setRequestState(RequestStateType::OPEN);
        $litter->setActionBy($loggedInUser);
        $litter->setRelationNumberKeeper($company->getOwner()->getRelationNumberKeeper());
        $litter->setUbn($location->getUbn());
        $litter->setIsHidden(false);
        $litter->setIsOverwrittenVersion(false);
        $litter->setMessageId(MessageBuilderBase::getNewRequestId());

        // Mother
        /** @var Ewe $mother */
        $repository = $this->getDoctrine()->getRepository(Constant::EWE_REPOSITORY);
        $contentMother = $content['mother'];

        if(key_exists('uln_country_code', $contentMother) && key_exists('uln_number', $contentMother)) {
            if ($contentMother['uln_country_code'] != '' && $contentMother['uln_number'] != '') {
                $mother = $repository->findOneBy([
                    'ulnCountryCode' => $contentMother['uln_country_code'],
                    'ulnNumber' => $contentMother['uln_number'],
                    'isAlive' => true
                ]);

                if ($mother == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE MOTHER IS NOT FOUND'
                    ], 428);
                }
            }
        }

        $motherCompany = $mother->getLocation()->getCompany();
        if($company != $motherCompany) {
            return new JsonResponse([
                Constant::CODE_NAMESPACE => 428,
                Constant::MESSAGE_NAMESPACE => 'THE MOTHER IS NOT IN YOUR LIVESTOCK'
            ], 428);
        }

        /** @var Litter $motherLitter */
        foreach($mother->getLitters() as $motherLitter) {
            $litterDate = $motherLitter->getLitterDate()->format('Y-m-d');
            $contentDate = (new \DateTime($content['date_of_birth']))->format('Y-m-d');

            if(($litterDate == $contentDate) && ($motherLitter->getStatus() == 'COMPLETED' || $motherLitter->getStatus() == 'OPEN')) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE MOTHER ALREADY HAS A LITTER REGISTERED ON THIS DATE'
                ], 428);
            }
        }

        $litter->setAnimalMother($mother);

        // Father
        $repository = $this->getDoctrine()->getRepository(Constant::RAM_REPOSITORY);
        $contentFather = $content['father'];

        $father = null;

        if(key_exists('pedigree_country_code', $contentFather) && key_exists('pedigree_number', $contentFather)) {
            if($contentFather['pedigree_country_code'] != '' && $contentFather['pedigree_number'] != '') {
                $father = $repository->findOneBy([
                    'pedigreeCountryCode' => $contentFather['pedigree_country_code'],
                    'pedigreeNumber' => $contentFather['pedigree_number'],
                    'isAlive' => true
                ]);

                if($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE PEDIGREE OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        if(key_exists('uln_country_code', $contentFather) && key_exists('uln_number', $contentFather)) {
            if ($contentFather['uln_country_code'] != '' && $contentFather['uln_number'] != '') {
                $father = $repository->findOneBy([
                    'ulnCountryCode' => $contentFather['uln_country_code'],
                    'ulnNumber' => $contentFather['uln_number'],
                    'isAlive' => true
                ]);

                if ($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        // Persist Litter
        $this->getDoctrine()->getManager()->persist($litter);

        // Children
        $repository = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY);
        $contentChildren = $content['children'];

        $isAliveCounter = 0;
        foreach($contentChildren as $contentChild) {

            // Child
            $contentGender = $contentChild['gender'];

            $child = new Neuter();
            if($contentGender == 'MALE') {
                $child = new Ram();

            }

            if($contentGender == 'FEMALE') {
                $child = new Ewe();
            }

            if($father != null) {
                $child->setParentFather($father);
            }

            $child->setLitter($litter);
            $child->setLocation($location);
            $child->setParentMother($mother);
            $child->setDateOfBirth(new \DateTime($content['date_of_birth']));
            $child->setBirthProgress($contentChild['birth_progress']);
            $child->setIsAlive(false);

            if($contentChild['birth_weight'] < 0 || $contentChild['birth_weight'] > 10) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE WEIGHT HAS TO BE BETWEEN 0 AND 10',
                    "data" => $contentChild['uln_country_code'] ." ". $contentChild['uln_number']
                ], 428);
            }

            if($contentChild['tail_length'] < 0 || $contentChild['tail_length'] > 10) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE TAIL LENGTH HAS TO BE BETWEEN 0 AND 30',
                    "data" => $contentChild['uln_country_code'] ." ". $contentChild['uln_number']
                ], 428);
            }


            if($contentChild['is_alive']) {
                $child->setIsAlive(true);

                // Tag
                $tag = $repository->findOneBy([
                    'ulnCountryCode' => $contentChild['uln_country_code'],
                    'ulnNumber' => $contentChild['uln_number'],
                    'tagStatus' => 'UNASSIGNED',
                    'owner' => $company->getOwner(),
                    'location' => $location
                ]);

                if($tag == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'YOU DO NOT OWN THIS UNASSIGNED TAG',
                        "data" => $contentChild['uln_country_code'] ." ". $contentChild['uln_number']
                    ], 428);
                }

                $tag->setTagStatus(TagStateType::ASSIGNING);
                $child->setUlnNumber($tag->getUlnNumber());
                $child->setUlnCountryCode($tag->getUlnCountryCode());
                $child->setAnimalOrderNumber($tag->getAnimalOrderNumber());
                $this->persist($tag);

                // Surrogate
                if($contentChild['nurture_type'] == 'SURROGATE') {
                    $repository = $this->getDoctrine()->getRepository(Constant::EWE_REPOSITORY);
                    $contentSurrogate = $contentChild['surrogate_mother'];
                    $surrogate = $repository->findOneBy([
                        'ulnCountryCode' => $contentSurrogate['uln_country_code'],
                        'ulnNumber' => $contentSurrogate['uln_number'],
                        'isAlive' => true
                    ]);

                    if($surrogate == null) {
                        return new JsonResponse([
                            Constant::CODE_NAMESPACE => 428,
                            Constant::MESSAGE_NAMESPACE => 'THE SURROGATE IS NOT IN YOUR LIVESTOCK'
                        ], 428);
                    }
                    $child->setSurrogate($surrogate);
                }

                // Lambar
                $child->setLambar(($contentChild['nurture_type'] == 'LAMBAR'));

                $animalDetails = new ArrayCollection();
                $animalDetails['animal'] = $child;
                $animalDetails['location'] = $location;
                $animalDetails['nurture_type'] = $contentChild['nurture_type'];
                $animalDetails['birth_type'] = $contentChild['birth_progress'];
                $animalDetails['birth_weight'] = $contentChild['birth_weight'];
                $animalDetails['tail_length'] = $contentChild['tail_length'];
                $animalDetails['litter_size'] = sizeof($contentChildren);

                // Persist Message
                $messageObject = $this->buildMessageObject(RequestType::DECLARE_BIRTH_ENTITY, $animalDetails, $client, $loggedInUser, $location);
                $this->sendMessageObjectToQueue($messageObject);
                $this->persist($messageObject);

                // Counter
                $isAliveCounter += 1;
            }

            if(!($child->getIsAlive())) {

                // Weight
                $weight = new Weight();
                $weight->setMeasurementDate(new \DateTime($content['date_of_birth']));
                $weight->setAnimal($child);
                $weight->setIsBirthWeight(true);
                $weight->setWeight($contentChild['birth_weight']);
                $this->getDoctrine()->getManager()->persist($weight);

                // Tail Length
                $tailLength = new TailLength();
                $tailLength->setMeasurementDate(new \DateTime($content['date_of_birth']));
                $tailLength->setAnimal($child);
                $tailLength->setLength($contentChild['tail_length']);
                $this->getDoctrine()->getManager()->persist($tailLength);

                // Persist Child & Add to litter
                $litter->addChild($child);
                $this->getDoctrine()->getManager()->persist($child);
            }
        }

        // Update & Persist Litter
        $litter->setBornAliveCount($isAliveCounter);
        $litter->setStillbornCount(sizeof($contentChildren)-$isAliveCounter);

        if($isAliveCounter == 0) {
            $litter->setStatus('COMPLETE');
            $litter->setRequestState(RequestStateType::FINISHED);
        }

        $this->getDoctrine()->getManager()->persist($litter);
        $this->getDoctrine()->getManager()->flush();

        // Action Log
        // DON'T PUT IN ABOVE OTHER CODE.. IT WILL MESS UP PERSIST SEQUENCE
        $log = ActionLogWriter::createBirth($manager, $client, $loggedInUser, $mother);

        // Complete Action Log
        ActionLogWriter::completeActionLog($manager, $log);

        return new JsonResponse([Constant::RESULT_NAMESPACE => 'ok'], 200);
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