<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthInspection;
use AppBundle\Output\HealthInspectionOutput;
use AppBundle\Validation\AdminValidator;
use Com\Tecnick\Barcode\Barcode;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\DataMapper\RadioListMapper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/api/v1/health_inspections")
 */
class HealthInspectionController extends APIController
{
    /**
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("GET")
    */
    public function getHealthInspections(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Get all NEW HealthInspections
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT 
                  location.ubn as ubn, 
                  person.last_name as last_name, 
                  person.first_name as first_name, 
                  MAX(scrapie.check_date) as scrapie_check_date
                FROM location
                    INNER JOIN location_health ON location.location_health_id = location_health.id
                    INNER JOIN scrapie ON location_health.id = scrapie.location_health_id
                    LEFT JOIN location_health_inspection ON location.id = location_health_inspection.location_id
                    INNER JOIN company ON location.company_id = company.id
                    INNER JOIN client ON company.owner_id = client.id
                    INNER JOIN person ON client.id = person.id
                WHERE 
                  location_health.current_scrapie_status = 'UNDER OBSERVATION' AND 
                  company.is_active = TRUE AND 
                  location.is_active = TRUE AND
                  location_health_inspection.location_id IS NULL
                GROUP BY location.ubn, person.last_name, person.first_name";
        $results = $em->getConnection()->query($sql)->fetchAll();
        $result = HealthInspectionOutput::createNewScrapieInspections($results);

        // Get all OTHER HealthInspections
        $sql = "SELECT
                    location_health_inspection.inspection_id,
                    location_health_inspection.inspection_subject,
                    location_health_inspection.request_date,
                    location_health_inspection.total_lead_time,
                    location_health_inspection.next_action,
                    location_health_inspection.status,
                    location.ubn,
                    person.last_name,
                    person.first_name,
                    authorizer.last_name AS authorizer_last_name,
                    authorizer.first_name AS authorizer_first_name,
                    action_taker.last_name AS action_taker_last_name,
                    action_taker.first_name AS action_taker_first_name
                FROM location_health_inspection
                    INNER JOIN location ON location_health_inspection.location_id = location.id
                    INNER JOIN company ON location.company_id = company.id
                    INNER JOIN client ON company.owner_id = client.id
                    INNER JOIN person ON client.id = person.id
                    LEFT JOIN person AS authorizer ON location_health_inspection.authorized_by_id = authorizer.id
                    LEFT JOIN person AS action_taker ON location_health_inspection.action_taken_by_id = action_taker.id;
        ";
        $results = $em->getConnection()->query($sql)->fetchAll();
        $result = array_merge($result, HealthInspectionOutput::createInspections($results));

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createInspection(Request $request) {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        $repository = $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY);
        $location = $repository->findOneBy(array('ubn' => $content->get('ubn'), 'isActive' => true));

        if($location) {
            $inspection = new LocationHealthInspection();
            $inspection->setLocation($location);
            $inspection->setInspectionSubject($content->get('inspection'));
            $inspection->setRequestDate(new \DateTime($content->get('request_date')));
            $inspection->setActionTakenBy($admin);
            $inspection->setStatus('ANNOUNCED');
            $inspection->setNextAction('SEND FORMS');

            /** @var Location $location */
            $location->getInspections()->add($inspection);

            // Save to Database
            $this->getDoctrine()->getManager()->persist($inspection);
            $this->getDoctrine()->getManager()->persist($location);
            $this->getDoctrine()->getManager()->flush();
            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $inspection), 200);
        }

        return new JsonResponse(array(
            Constant::CODE_NAMESPACE => 400,
            Constant::MESSAGE_NAMESPACE => 'LOCATION NOT FOUND'
        ), 400);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("PUT")
     */
    public function changeInspection(Request $request) {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        $repository = $this->getDoctrine()->getRepository(LocationHealthInspection::class);
        $inspection = $repository->findOneBy(array('inspectionId' => $content->get('inspection_id')));

        if($inspection) {
            if($inspection->getStatus() == 'ANNOUNCED') {
                $inspection->setStatus('ONGOING');
                $inspection->setNextAction('RECEIVE RESULTS');

                // TODO ADD DIRECTION

                $inspection->setActionTakenBy($admin);
            }
            elseif($inspection->getStatus() == 'ONGOING') {
                $inspection->setStatus('AUTHORIZATION');
                $inspection->setNextAction('AUTHORIZE');

                // TODO ADD DIRECTION

                $inspection->setActionTakenBy($admin);
            }
            elseif($inspection->getStatus() == 'AUTHORIZATION') {
                $inspection->setStatus('FINISHED');
                $inspection->setNextAction('NOTHING');

                $inspection->setActionTakenBy($admin);
                $inspection->setAuthorizedBy($admin);
            }

            // Save to Database
            $this->getDoctrine()->getManager()->persist($inspection);
            $this->getDoctrine()->getManager()->flush();

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $inspection), 200);
        }

        return new JsonResponse(array(
            Constant::CODE_NAMESPACE => 400,
            Constant::MESSAGE_NAMESPACE => 'INSPECTION NOT FOUND'
        ), 400);
    }


    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/barcodes")
     * @Method("POST")
     */
    public function createBarcodes(Request $request) {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $twigFile = 'animal_health/barcodes.html.twig';

        $content = $this->getContentAsArray($request);

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT
                    animal.uln_country_code, animal.uln_number
                  FROM animal
                    INNER JOIN location ON animal.location_id = location.id
                  WHERE
                    location.ubn = '".$content['ubn']."' AND location.is_active = TRUE AND animal.is_alive = TRUE
                  ORDER BY animal.animal_order_number ASC";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $barcodes = array();
        foreach ($results as $result) {
            $uln_country_code = $result['uln_country_code'];
            $uln_number = $result['uln_number'];

            $barcode = new Barcode();
            $barcodeObj = $barcode->getBarcodeObj('C128', $uln_country_code. ' ' .$uln_number, 0.5, 40);

            $barcodes[] = [
                "barcode" => $barcodeObj->getHtmlDiv(),
                "uln_country_code" => $uln_country_code,
                "uln_number_1" => substr($uln_number, 0, -5),
                "uln_number_2" => substr($uln_number, -5)
            ];
        }

        $html = $this->renderView($twigFile, ['barcodes' => $barcodes]);

        $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,
            array(
                'orientation' => 'Portrait',
                'default-header'=> false,
                'disable-smart-shrinking' => false,
                'page-height' => 25,
                'page-width' => 100,
                'margin-top'    => 2,
                'margin-bottom' => 0,
                'margin-left'   => 0,
                'margin-right'  => 0
            ));

        $s3Service = $this->getStorageService();

        $dateTimeNow = new \DateTime();
        $datePrint = $dateTimeNow->format('Y-m-d_').$dateTimeNow->format('H').'h'.$dateTimeNow->format('i').'m'.$dateTimeNow->format('s').'s';

        $filename = 'barcode-'.$datePrint.'.pdf';
        $url = $s3Service->uploadPdf($pdfOutput, 'reports/'.$admin->getId().'/'.$filename);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $url), 200);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/letter")
     * @Method("POST")
     */
    public function createLetter(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $twigFile = 'animal_health/base_letter.html.twig';

        $content = $this->getContentAsArray($request);
        $ubn = $content->get('ubn');
        $illness = $content->get('illness');
        $letter_type = $content->get('letter_type');
        $type = strtoupper($illness . '_' . $letter_type);

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT
                  company.company_name,
                  address.street_name,
                  address.address_number,
                  address.address_number_suffix,
                  province.name as state,
                  address.postal_code,
                  person.first_name,
                  person.last_name
                FROM
                  location
                  INNER JOIN company ON location.company_id = company.id
                  INNER JOIN address ON company.billing_address_id = address.id
                  INNER JOIN person ON company.owner_id = person.id
                  LEFT JOIN province ON address.state = province.code
                WHERE
                  location.ubn = '". $ubn ."' AND  location.is_active = TRUE;";
        $addressee = $em->getConnection()->query($sql)->fetch();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT
                  location_health_letter.html
                FROM location_health_letter
                    INNER JOIN person ON location_health_letter.updated_by_id = person.id              
                WHERE 
                  location_health_letter.type = '". $type ."'
                ORDER BY location_health_letter.log_date DESC LIMIT 1";
        $letter_template = $em->getConnection()->query($sql)->fetch();

        $now = new \DateTime('now');
        $now = $now->format('d/m/Y');
        $html = $this->renderView($twigFile, ['addressee' => $addressee, 'letter_template' => $letter_template, 'now' => $now]);
        $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,
            array(
                'orientation' => 'Portrait',
                'images' => true,
                'default-header'=> false,
                'page-size' => 'A4',
                'margin-top'    => 20,
                'margin-bottom' => 20,
                'margin-left'   => 20,
                'margin-right'  => 20
            ));

        $s3Service = $this->getStorageService();

        $dateTimeNow = new \DateTime();
        $datePrint = $dateTimeNow->format('Y-m-d_').$dateTimeNow->format('H').'h'.$dateTimeNow->format('i').'m'.$dateTimeNow->format('s').'s';

        $filename = 'health-letter-'.$letter_type.'-'.$datePrint.'.pdf';
        $url = $s3Service->uploadPdf($pdfOutput, 'reports/'.$admin->getId().'/'.$filename);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $url), 200);
    }
}