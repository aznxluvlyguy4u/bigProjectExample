<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\FTPFailedImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthInspection;
use AppBundle\Entity\LocationHealthInspectionDirection;
use AppBundle\Entity\LocationHealthInspectionResult;
use AppBundle\FormInput\LocationHealthEditor;
use AppBundle\Output\HealthInspectionOutput;
use AppBundle\Validation\AdminValidator;
use Com\Tecnick\Barcode\Barcode;
use Doctrine\Common\Collections\ArrayCollection;
use PHPExcel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Extension\Core\DataMapper\RadioListMapper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/api/v1/health_inspections")
 */
class LocationHealthInspectionAPIController extends APIController
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
        $newInspections = HealthInspectionOutput::createNewScrapieInspections($results);

        // Get all OTHER HealthInspections
        $sql = "SELECT
                  location_health_inspection.id,
                  location_health_inspection.inspection_id,
                  location_health_inspection.inspection_subject,
                  location_health_inspection.request_date,
                  location_health_inspection.total_lead_time,
                  location_health_inspection.next_action,
                  location_health_inspection.status,
                  location.ubn,
                  person.last_name,
                  person.first_name
                FROM location_health_inspection
                  INNER JOIN location ON location_health_inspection.location_id = location.id
                  INNER JOIN company ON location.company_id = company.id
                  INNER JOIN client ON company.owner_id = client.id
                  INNER JOIN person ON client.id = person.id
        ";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $otherInspections = [];
        foreach($results as $result) {
            $sql = "SELECT
                      location_health_inspection_direction.direction_type,
                      location_health_inspection_direction.direction_date,
                      person.first_name,
                      person.last_name
                    FROM location_health_inspection_direction
                      INNER JOIN location_health_inspection ON location_health_inspection_direction.inspection_id = location_health_inspection.id
                      INNER JOIN person ON location_health_inspection_direction.action_taken_by_id = person.id
                    WHERE location_health_inspection_direction.inspection_id = ".$result['id']." 
                    ORDER BY location_health_inspection_direction.direction_date DESC";
            $directions = $em->getConnection()->query($sql)->fetchAll();
            $otherInspections[] = HealthInspectionOutput::createInspections($result, $directions);
        }

        $inspections = array_merge($newInspections, $otherInspections);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $inspections), 200);
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
            $em = $this->getDoctrine()->getManager();
            $sql = "SELECT COUNT(id)
                    FROM location_health_inspection
                    WHERE location_health_inspection.ubn = '".$content->get("ubn")."'
                    AND location_health_inspection.inspection = '".$content->get("inspection")."'";
            $result = $em->getConnection()->query($sql)->fetch();
            $orderNumber = $result[0] + 1;
            $orderYear = date("Y");
            $orderLaboratory = "";
            if($content->get("inspection") == "SCRAPIE") {
                $orderLaboratory = "2";
            }

            if($content->get("inspection") == "MAEDI VISNA") {
                $orderLaboratory = "1";
            }

            $count = 1000 + $orderNumber;
            if($count > 99999) {
                $count = $orderNumber;
            }

            $orderCount = strval($count);
            if($count < 10000) {
                $orderCount = '0' . strval($count);
            }

            if($count < 1000) {
                $orderCount = '00' . strval($count);
            }

            if($count < 100) {
                $orderCount = '000' . strval($count);
            }

            if($count < 10) {
                $orderCount = '0000' . strval($count);
            }

            $order = $orderYear . $orderLaboratory . $orderCount;

            $inspection = new LocationHealthInspection();
            $inspection->setLocation($location);
            $inspection->setInspectionSubject($content->get('inspection'));
            $inspection->setRequestDate(new \DateTime($content->get('request_date')));
            $inspection->setStatus('ANNOUNCED');
            $inspection->setNextAction('SEND FORMS');
            $inspection->setOrderNumber($order);

            $direction = new LocationHealthInspectionDirection();
            $direction->setDirectionDate(new \DateTime());
            $direction->setDirectionType('ANNOUNCED');
            $direction->setActionTakenBy($admin);
            $direction->setInspection($inspection);

            $inspection->getDirections()->add($direction);

            /** @var Location $location */
            $location->getInspections()->add($inspection);

            // Save to Database
            $this->getDoctrine()->getManager()->persist($inspection);
            $this->getDoctrine()->getManager()->persist($direction);
            $this->getDoctrine()->getManager()->persist($location);
            $this->getDoctrine()->getManager()->flush();

            $output = array(
                "inspection_id" => $inspection->getInspectionId(),
                "inspection" => $inspection->getInspectionSubject(),
                "next_action" => $inspection->getNextAction(),
                "request_date" => $inspection->getRequestDate(),
                "action_taken_by" => array(
                    "first_name" => $admin->getFirstName(),
                    "last_name" => $admin->getLastName(),
                ),
                "status" => $inspection->getStatus()
            );

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $output), 200);
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
            $status = '';
            $nextAction = '';
            $directions = [];
            $now = new \DateTime();
            $totalLeadTime = 0;

            if($inspection->getStatus() == 'ANNOUNCED') {
                $status = 'ONGOING';
                $nextAction = 'RECEIVE RESULTS';
            }
            elseif($inspection->getStatus() == 'ONGOING') {
                $status = 'AUTHORIZATION';
                $nextAction = 'AUTHORIZE';
            }
            elseif($inspection->getStatus() == 'AUTHORIZATION') {
                $status = 'FINISHED';
                $nextAction = '';
                $interval = $inspection->getRequestDate()->diff($now);
                $totalLeadTime = $interval->days;
                $directions = $inspection->getDirections();
                $inspection->setEndDate($now);
                $inspection->setTotalLeadTime($totalLeadTime);
            }

            $inspection->setStatus($status);
            $inspection->setNextAction($nextAction);

            $direction = new LocationHealthInspectionDirection();
            $direction->setDirectionDate($now);
            $direction->setDirectionType($status);
            $direction->setActionTakenBy($admin);
            $direction->setInspection($inspection);

            $inspection->getDirections()->add($direction);

            $this->getDoctrine()->getManager()->persist($inspection);
            $this->getDoctrine()->getManager()->persist($direction);

            if($status == 'FINISHED') {
                LocationHealthEditor::edit($this->getDoctrine()->getManager(), $inspection->getLocation(), $content);
            }
            // Save to Database
            $this->getDoctrine()->getManager()->flush();

            $output = array(
                "inspection_id" => $inspection->getInspectionId(),
                "inspection" => $inspection->getInspectionSubject(),
                "next_action" => $inspection->getNextAction(),
                "request_date" => $inspection->getRequestDate(),
                "total_lead_time" => $totalLeadTime,
                "directions" => $directions,
                "action_taken_by" => array(
                    "first_name" => $admin->getFirstName(),
                    "last_name" => $admin->getLastName(),
                ),
                "status" => $inspection->getStatus()
            );

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $output), 200);
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
        $dateTimeNow = new \DateTime();

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $twigFile = 'animal_health/barcodes.html.twig';

        $content = $this->getContentAsArray($request);

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT
                    animal.uln_country_code, animal.uln_number, location.ubn, person.first_name, person.last_name
                  FROM animal
                    INNER JOIN location ON animal.location_id = location.id
                    INNER JOIN company ON location.company_id = company.id
                    INNER JOIN person ON company.owner_id = person.id
                  WHERE
                    location.ubn = '".$content['ubn']."' AND location.is_active = TRUE AND animal.is_alive = TRUE
                  ORDER BY animal.animal_order_number DESC";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $owner = [];
        if(sizeof($results) > 0) {
            $owner['ubn'] = $results[0]['ubn'];
            $owner['first_name'] = $results[0]['first_name'];
            $owner['last_name'] = $results[0]['last_name'];
        }

        $barcodes = [];
        foreach ($results as $result) {
            $uln_country_code = $result['uln_country_code'];
            $uln_number = $result['uln_number'];

            $barcode = new Barcode();
            $barcodeObj = $barcode->getBarcodeObj('C128', $uln_country_code. ' ' .$uln_number, -1, 54);
//            $barcodeObj->setSize(162, 54);
//            dump($barcodeObj->getHtmlDiv()); die;

            $barcodes[] = [
                "barcode" => $barcodeObj->getHtmlDiv(),
                "uln_country_code" => $uln_country_code,
                "uln_number_1" => substr($uln_number, 0, -5),
                "uln_number_2" => substr($uln_number, -5)
            ];
        }

        $results["order_number"] = "";
        if($content['inspection_id']) {
            $sql = "SELECT location_health_inspection.order_number
                    FROM location_health_inspection
                    WHERE location_health_inspection.inspection_id = '".$content['inspection_id']."'";
            $results = $em->getConnection()->query($sql)->fetch();
        }
        $html = $this->renderView($twigFile, [
            'barcodes' => $barcodes,
            'owner' => $owner,
            'orderNumber' => $results["order_number"],
            'now' => $dateTimeNow->format('d-m-Y')]);

        $pdfOutput = $this->get('knp_snappy.pdf')->getOutputFromHtml($html,
            array(
                'orientation' => 'Portrait',
                'default-header'=> false,
                'disable-smart-shrinking' => false,
                'page-height' => 25,
                'page-width' => 100,
                'margin-top'    => 2,
                'margin-bottom' => 2,
                'margin-left'   => 0,
                'margin-right'  => 2,
                'viewport-size' => '640x480'
            ));

        $s3Service = $this->getStorageService();
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

        $contents = $this->getContentAsArray($request);

        $letters = [];
        foreach ($contents as $content) {
            $ubn = $content['ubn'];
            $illness = $content['illness'];
            $letter_type = $content['letter_type'];
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
                      location.ubn = '". $ubn ."' AND  location.is_active = TRUE";
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

            $letter = ['addressee' => $addressee, 'letter_template' => $letter_template];
            $letters[] = $letter;
        }

        $now = new \DateTime('now');

        $months[0] = 'januari';
        $months[1] = 'februari';
        $months[2] = 'maart';
        $months[3] = 'april';
        $months[4] = 'mei';
        $months[5] = 'juni';
        $months[6] = 'juli';
        $months[7] = 'augustus';
        $months[8] = 'september';
        $months[9] = 'oktober';
        $months[10] = 'november';
        $months[11] = 'december';

        $now = $now->format('d') . ' ' . $months[($now->format('m') - 1)] . ' ' . $now->format('Y');
        $html = $this->renderView($twigFile, ['letters' => $letters, 'now' => $now]);
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

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/results")
     * @Method("POST")
     */
    public function createInspectionResults(Request $request)
    {
        $contentResults = $this->getContentAsArray($request);
        $ubn = $contentResults->get('ubn');
        $illness = $contentResults->get('illness');

        $repository = $this->getDoctrine()->getRepository(Location::class);
        $location = $repository->findOneBy(['ubn' => $ubn]);

        if($location == null) {
            return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'LOCATION: '. $ubn .' NOT FOUND'), 400);
        }

        $repository = $this->getDoctrine()->getRepository(LocationHealthInspection::class);
        $inspection = $repository->findOneBy(['location' => $location, 'inspectionSubject' => $illness, 'status' => 'ONGOING']);

        if($inspection == null) {
            return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'INSPECTION (UBN: '. $ubn .') NOT FOUND'), 400);
        }

        /** @var LocationHealthInspection $inspection */
        $inspection->setResults(new ArrayCollection());
        $this->getDoctrine()->getManager()->persist($inspection);

        foreach($contentResults->get('results') as $contentResult) {
            $ulnCountryCode = $contentResult['uln_country_code'];
            $ulnNumber = $contentResult['uln_number'];

            $repository = $this->getDoctrine()->getRepository(Animal::class);
            $animal = $repository->findOneBy(['ulnCountryCode' => $ulnCountryCode, 'ulnNumber' => $ulnNumber, 'location' => $location]);

            if($animal == null) {
                return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ANIMAL: '. $ulnCountryCode.$ulnNumber .' NOT FOUND'), 400);
            }

            $result = new LocationHealthInspectionResult();
            $result->setInspection($inspection);
            $result->setAnimal($animal);
            $result->setCustomerSampleId($contentResult['customer_sample_id']);
            $result->setMgxSampleId($contentResult['mgx_sample_id']);
            $result->setGenotype($contentResult['genotype']);
            $result->setGenotypeWithCondon($contentResult['genotype_with_condon']);
            $result->setGenotypeClass($contentResult['genotype_class']);
            $result->setReceptionDate(new \DateTime($contentResult['reception_date']));
            $result->setResultDate(new \DateTime($contentResult['result_date']));

            // Save to Database
            $this->getDoctrine()->getManager()->persist($result);
        }

        $inspection->setStatus('AUTHORIZATION');
        $this->getDoctrine()->getManager()->persist($inspection);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request the request object
     * @param string $inspectionId
     * @return JsonResponse
     * @Route("/{inspectionId}/results")
     * @Method("GET")
     */
    public function getInspectionResults(Request $request, $inspectionId) {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT
                  animal.uln_country_code,
                  animal.uln_number,
                  animal.date_of_birth,
                  location_health_inspection_result.result_date,
                  location_health_inspection_result.reception_date,
                  location_health_inspection_result.customer_sample_id,
                  location_health_inspection_result.genotype,
                  location_health_inspection_result.genotype_with_condon,
                  location_health_inspection_result.genotype_class,
                  location_health_inspection_result.mgx_sample_id
                FROM
                  location_health_inspection_result
                  INNER JOIN animal ON location_health_inspection_result.animal_id = animal.id
                  INNER JOIN location_health_inspection ON location_health_inspection_result.inspection_id = location_health_inspection.id
                  INNER JOIN location ON location_health_inspection.location_id = location.id
                WHERE
                  location_health_inspection.inspection_id = '".$inspectionId."'";

        $results = $em->getConnection()->query($sql)->fetchAll();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $results), 200);
    }


    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/failed-results")
     * @Method("POST")
     */
    public function createFailedInspectionResults(Request $request) {
        $contentResults = $this->getContentAsArray($request);
        $filename = $contentResults->get('filename');
        $url = $contentResults->get('url');
        $serverName = $contentResults->get('server_name');

        // Create Failed Import
        $failedImport = new FTPFailedImport();
        $failedImport->setFilename($filename);
        $failedImport->setUrl($url);
        $failedImport->setServerName($serverName);

        // Save to Database
        $this->getDoctrine()->getManager()->persist($failedImport);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/failed-results")
     * @Method("GET")
     */
    public function getFailedInspectionResults(Request $request) {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $repository = $this->getDoctrine()->getRepository(Location::class);
        $failedImports = $repository->findAll();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $failedImports), 200);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/failed-results")
     * @Method("PUT")
     */
    public function changeFailedInspectionResults(Request $request) {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $contentResults = $this->getContentAsArray($request);
        $illness = $contentResults->get('illness');
        $file = $contentResults->get('file');

        if ($file != null) {
            if ($file['extension'] == 'xls') {
                $fileLocation = $this->getParameter('kernel.cache_dir').'/'.$file['filename'];
                file_put_contents($fileLocation, $file);

                /** @var PHPExcel $phpExcelObject */
                $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject($fileLocation);
                $phpExcelObject->setActiveSheetIndex(0);
                $ubn = $phpExcelObject->getActiveSheet()->getCellByColumnAndRow(1, 4);

                $nRows = $phpExcelObject->getActiveSheet()->getHighestRow();
                $results = [];
                try {
                    for ($row = 6; $row < $nRows; ++$row) {
                        $customerSampleID = $phpExcelObject->getActiveSheet()->getCellByColumnAndRow(3, $row);
                        $mgxSampleID = $phpExcelObject->getActiveSheet()->getCellByColumnAndRow(4, $row);
                        $genotype = $phpExcelObject->getActiveSheet()->getCellByColumnAndRow(5, $row);
                        $genotypeWithCondon = $phpExcelObject->getActiveSheet()->getCellByColumnAndRow(6, $row);
                        $genotypeClass = $phpExcelObject->getActiveSheet()->getCellByColumnAndRow(7, $row);
                        $receptionDate = new \DateTime($phpExcelObject->getActiveSheet()->getCellByColumnAndRow(8, $row));
                        $resultDate = $phpExcelObject->getActiveSheet()->getCellByColumnAndRow(9, $row);
                        $ulnCountryCode = mb_substr($customerSampleID, 0, 2);
                        $ulnNumber = mb_substr($customerSampleID, 2);

                        $result = [
                            "uln_country_code" => $ulnCountryCode,
                            "uln_number" => $ulnNumber,
                            "customer_sample_id" => $customerSampleID,
                            "mgx_sample_id" => $mgxSampleID,
                            "genotype" => $genotype,
                            "genotype_with_condon" => $genotypeWithCondon,
                            "genotype_class" => $genotypeClass,
                            "reception_date" => $receptionDate,
                            "result_date" => $resultDate
                        ];

                        $results[] = $result;
                    }
                } catch (Exception $e) {
                    return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'THE FILE IS INVALID'), 400);
                }

                $request = [
                    "ubn" => $ubn,
                    "illness" => $illness,
                    "results" => $results
                ];

                $response = $this->forward('AppBundle:LocationHealthInspectionAPIController:createInspectionResults', $request);

                // Send to S3 Bucket

                // Remove record from Failed FTP File
                unlink($fileLocation);
                return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
            }
        }

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'THERE IS NO FILE'), 400);

    }

}