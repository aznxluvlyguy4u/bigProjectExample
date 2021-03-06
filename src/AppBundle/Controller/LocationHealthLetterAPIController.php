<?php

namespace AppBundle\Controller;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\LocationHealthLetter;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LocationHealthLetterAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/health_location_letters")
 */
class LocationHealthLetterAPIController extends APIController
{
    /**
     * @Route("/{illness}/{letter_type}")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getLocationHealthLetter(Request $request, $illness, $letter_type)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $type = strtoupper($illness . '_' . $letter_type);
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT 
                  location_health_letter.log_date, 
                  location_health_letter.html, 
                  location_health_letter.revision_number, 
                  person.first_name,
                  person.last_name
                FROM location_health_letter
                    INNER JOIN person ON location_health_letter.updated_by_id = person.id              
                WHERE 
                  location_health_letter.type = '". $type ."'
                ORDER BY location_health_letter.log_date DESC LIMIT 1";
        $result = $em->getConnection()->query($sql)->fetch();

        if($result === null) { $result = []; }

        return ResultUtil::successResult($result);
    }

    /**
     * @Route("")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createLocationHealthLetter(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArrayCollection($request);
        // TODO VALIDATE CONTENT

        $type = strtoupper($content->get('illness') . '_' . $content->get('letter_type'));

        $repository = $this->getDoctrine()->getRepository(LocationHealthLetter::class);
        $locationHealthLetters = $repository->findBy(array('type' => $type));
        $revisionNumber = sizeof($locationHealthLetters) + 1;


        $locationHealthLetter = new LocationHealthLetter();
        $locationHealthLetter->setHtml($content->get('html'));
        $locationHealthLetter->setRevisionNumber($revisionNumber);
        $locationHealthLetter->setType($type);
        $locationHealthLetter->setUpdatedBy($admin);

        // Save to Database
        $this->getDoctrine()->getManager()->persist($locationHealthLetter);
        $this->getDoctrine()->getManager()->flush();

        ResultUtil::successResult('ok');
    }
}
