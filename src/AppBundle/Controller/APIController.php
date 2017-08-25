<?php

namespace AppBundle\Controller;

use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\ServiceId;
use AppBundle\Service\AdminAuthService;
use AppBundle\Service\AdminProfileService;
use AppBundle\Service\AdminService;
use AppBundle\Service\AnimalLocationHistoryService;
use AppBundle\Service\AnimalService;
use AppBundle\Service\ArrivalService;
use AppBundle\Service\AuthService;
use AppBundle\Service\AwsExternalQueueService;
use AppBundle\Service\AwsInternalQueueService;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\BirthService;
use AppBundle\Service\CacheService;
use AppBundle\Service\ClientService;
use AppBundle\Service\EmailService;
use AppBundle\Service\EntityGetter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\HealthUpdaterService;
use AppBundle\Service\IRSerializer;
use AppBundle\Service\Migration\ClientMigrator;
use AppBundle\Service\MixBlupInputQueueService;
use AppBundle\Service\MixBlupOutputQueueService;
use AppBundle\Service\Report\BreedValuesOverviewReportService;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Service\Report\LiveStockReportService;
use AppBundle\Service\Report\PedigreeCertificateReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIController
 * @package AppBundle\Controller
 */
class APIController extends Controller implements APIControllerInterface
{
  /** @var array */
  private $services = [
      ServiceId::ADMIN_AUTH_SERVICE => null,
      ServiceId::ADMIN_SERVICE => null,
      ServiceId::ADMIN_PROFILE_SERVICE => null,
      ServiceId::ANIMAL_LOCATION_HISTORY => null,
      ServiceId::ANIMAL_SERVICE => null,
      ServiceId::AUTH_SERVICE => null,
      ServiceId::ARRIVAL_SERVICE => null,
      ServiceId::BIRTH_SERVICE => null,
      ServiceId::BREED_VALUES_OVERVIEW_REPORT => null,
      ServiceId::CACHE => null,
      ServiceId::CLIENT_MIGRATOR => null,
      ServiceId::CLIENT_SERVICE => null,
      ServiceId::EMAIL_SERVICE => null,
      ServiceId::ENTITY_GETTER => null,
      ServiceId::EXCEL_SERVICE => null,
      ServiceId::EXTERNAL_QUEUE_SERVICE => null,
      ServiceId::HEALTH_UPDATER_SERVICE => null,
      ServiceId::INBREEDING_COEFFICIENT_REPORT_SERVICE => null,
      ServiceId::INTERNAL_QUEUE_SERVICE => null,
      ServiceId::LIVESTOCK_REPORT => null,
      ServiceId::LOGGER => null,
      ServiceId::MIXBLUP_INPUT_QUEUE_SERVICE => null,
      ServiceId::MIXBLUP_OUTPUT_QUEUE_SERVICE => null,
      ServiceId::PEDIGREE_CERTIFICATES_REPORT => null,
      ServiceId::PEDIGREE_REGISTER_REPORT => null,
      ServiceId::REDIS_CLIENT => null,
      ServiceId::REQUEST_MESSAGE_BUILDER => null,
      ServiceId::SERIALIZER => null,
      ServiceId::STORAGE_SERVICE => null,
      ServiceId::USER_SERVICE => null,
  ];


  /**
   * @param string $controller
   * @return mixed|null
   */
  private function getService($controller){
    if(!key_exists($controller, $this->services)) { return null;}

    if ($this->services[$controller] == null) {
      $this->services[$controller] = $this->get($controller);
    }
    return $this->services[$controller];
  }

  /** @return AdminAuthService */
  protected function getAdminAuthService(){ return $this->getService(ServiceId::ADMIN_AUTH_SERVICE); }
  /** @return AdminService */
  protected function getAdminService(){ return $this->getService(ServiceId::ADMIN_SERVICE); }
  /** @return AdminProfileService */
  protected function getAdminProfileService(){ return $this->getService(ServiceId::ADMIN_PROFILE_SERVICE); }
  /** @return AnimalLocationHistoryService */
  protected function getAnimalLocationHistoryService(){ return $this->getService(ServiceId::ANIMAL_LOCATION_HISTORY); }
  /** @return AnimalService */
  protected function getAnimalService(){ return $this->getService(ServiceId::ANIMAL_SERVICE); }
  /** @return AuthService */
  protected function getAuthService(){ return $this->getService(ServiceId::AUTH_SERVICE); }
  /** @return ArrivalService */
  protected function getArrivalService(){ return $this->getService(ServiceId::ARRIVAL_SERVICE); }
  /** @return BirthService */
  protected function getBirthService(){ return $this->getService(ServiceId::BIRTH_SERVICE); }
  /** @return BreedValuesOverviewReportService */
  protected function getBreedValuesOverviewReportService() { return $this->getService(ServiceId::BREED_VALUES_OVERVIEW_REPORT); }
  /** @return CacheService */
  protected function getCacheService(){ return $this->getService(ServiceId::CACHE); }
  /** @return ClientMigrator */
  protected function getClientMigratorService(){ return $this->getService(ServiceId::CLIENT_MIGRATOR); }
  /** @return ClientService */
  protected function getClientService(){ return $this->getService(ServiceId::CLIENT_SERVICE); }
  /** @return EmailService */
  protected function getEmailService() { return $this->getService(ServiceId::EMAIL_SERVICE); }
  /** @return EntityGetter */
  protected function getEntityGetter() { return $this->getService(ServiceId::ENTITY_GETTER); }
  /** @return ExcelService */
  protected function getExcelService() { return $this->getService(ServiceId::EXCEL_SERVICE); }
  /** @return AwsExternalQueueService */
  protected function getExternalQueueService(){ return $this->getService(ServiceId::EXTERNAL_QUEUE_SERVICE); }
  /** @return HealthUpdaterService */
  protected function getHealthUpdaterService(){ return $this->getService(ServiceId::HEALTH_UPDATER_SERVICE); }
  /** @return InbreedingCoefficientReportService */
  protected function getInbreedingCoefficientReportService() { return $this->getService(ServiceId::INBREEDING_COEFFICIENT_REPORT_SERVICE); }
  /** @return AwsInternalQueueService */
  protected function getInternalQueueService() { return $this->getService(ServiceId::INTERNAL_QUEUE_SERVICE); }
  /** @return LiveStockReportService */
  protected function getLiveStockReportService() { return $this->getService(ServiceId::LIVESTOCK_REPORT); }
  /** @return Logger */
  protected function getLogger() { return $this->getService(ServiceId::LOGGER); }
  /** @return MixBlupInputQueueService */
  protected function getMixBlupInputQueueService() { return $this->getService(ServiceId::MIXBLUP_INPUT_QUEUE_SERVICE); }
  /** @return MixBlupOutputQueueService */
  protected function getMixBlupOutputQueueService() { return $this->getService(ServiceId::MIXBLUP_OUTPUT_QUEUE_SERVICE); }
  /** @return PedigreeCertificateReportService */
  protected function getPedigreeCertificateReportService() { return $this->getService(ServiceId::PEDIGREE_CERTIFICATES_REPORT); }
  /** @return PedigreeRegisterOverviewReportService */
  protected function getPedigreeRegisterReportService() { return $this->getService(ServiceId::PEDIGREE_REGISTER_REPORT); }
  /** @return \Redis */
  protected function getRedisClient() { return $this->getService(ServiceId::REDIS_CLIENT); }
  /** @return RequestMessageBuilder */
  protected function getRequestMessageBuilder() { return $this->getService(ServiceId::REQUEST_MESSAGE_BUILDER); }
  /** @return IRSerializer */
  protected function getSerializer() { return $this->getService(ServiceId::SERIALIZER);  }
  /** @return AWSSimpleStorageService */
  protected function getStorageService(){ return $this->getService(ServiceId::STORAGE_SERVICE); }
  /** @return UserService */
  protected function getUserService(){ return $this->getService(ServiceId::USER_SERVICE); }


  /**
   * Redirect to API docs when root is requested
   *
   * @Route("")
   * @Method("GET")
   */
  public function redirectRootToAPIDoc()
  {
    return new RedirectResponse('/api/v1/doc');
  }

  /**
   * @param Request $request
   * @return Client|null
   */
  public function getAccountOwner(Request $request = null)
  {
    return $this->getUserService()->getAccountOwner($request);
  }


  /**
   * @param string $tokenCode
   * @return Employee|null
   */
  public function getEmployee($tokenCode = null)
  {
    return $this->getUserService()->getEmployee($tokenCode);
  }


  /**
   * @param Request $request
   * @return Location|null
   */
  public function getSelectedLocation(Request $request)
  {
      return $this->getUserService()->getSelectedLocation($request);
  }


}