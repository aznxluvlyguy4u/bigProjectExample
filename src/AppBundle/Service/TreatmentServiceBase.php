<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Treatment;
use AppBundle\Entity\TreatmentRepository;
use AppBundle\Entity\TreatmentLocation;
use AppBundle\Entity\TreatmentLocationRepository;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Entity\TreatmentMedicationRepository;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Entity\TreatmentTemplateRepository;
use AppBundle\Entity\TreatmentType;
use AppBundle\Entity\TreatmentTypeRepository;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\Validator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentServiceBase
 * @package AppBundle\Service
 */
class TreatmentServiceBase extends DeclareControllerServiceBase
{
    /** @var TreatmentRepository */
    protected $treatmentAnimalRepository;
    /** @var TreatmentLocationRepository */
    protected $treatmentLocationRepository;
    /** @var TreatmentTemplateRepository */
    protected $treatmentTemplateRepository;
    /** @var TreatmentTypeRepository */
    protected $treatmentTypeRepository;
    /** @var TreatmentMedicationRepository */
    protected $treatmentMedicationRepository;

    /** @var string */
    protected $actionLogDescription;

    /** @var string */
    protected $actionLogName;

    /**
     * @required
     */
    public function onInit()
    {
        $this->treatmentAnimalRepository = $this->getManager()->getRepository(Treatment::class);
        $this->treatmentLocationRepository = $this->getManager()->getRepository(TreatmentLocation::class);
        $this->treatmentTemplateRepository = $this->getManager()->getRepository(TreatmentTemplate::class);
        $this->treatmentTypeRepository = $this->getManager()->getRepository(TreatmentType::class);
        $this->treatmentMedicationRepository = $this->getManager()->getRepository(TreatmentMedication::class);
    }


    /**
     * @param Request $request
     * @return array
     */
    protected function getJmsGroupByQuery(Request $request)
    {
        if(RequestUtil::getBooleanQuery($request,QueryParameter::MINIMAL_OUTPUT,true)) {
            return [JmsGroup::TREATMENT_TEMPLATE_MIN];
        }
        return [JmsGroup::TREATMENT_TEMPLATE];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getJmsGroupByQueryForTreatment(Request $request)
    {
        if(RequestUtil::getBooleanQuery($request,QueryParameter::MINIMAL_OUTPUT,true)) {
            return [JmsGroup::TREATMENT_MIN];
        }
        return [JmsGroup::TREATMENT];
    }

    /**
     * @param string|int $ubn
     * @return JsonResponse|\AppBundle\Entity\Location|null
     */
    protected function getLocationByUbn($ubn)
    {
        if (!ctype_digit($ubn) && !is_int($ubn)) {
            return Validator::createJsonResponse('UBN must be a number', 428);
        }

        $location = $this->getManager()->getRepository(Location::class)->findOneByActiveUbn($ubn);
        if ($location === null) {
            return Validator::createJsonResponse('No active location found for given UBN', 428);
        }
        return $location;
    }


    /**
     * @param Location $location
     * @return JsonResponse|bool
     */
    protected function validateIfLocationBelongsToClient($location)
    {
        $user = $this->getUser();
        if ($user instanceof Client) {
            //A client is only allowed to see own templates
            if ($location->getOwner()) {
                if ($location->getOwner()->getId() !== $user->getId()) {
                    return Validator::createJsonResponse('UNAUTHORIZED', 401);
                }
            }
        }
        return true;
    }

    /**
     * @param int $templateId
     * @param string $type
     * @param boolean $ignoreIsActiveState
     * @return JsonResponse|TreatmentTemplate|null|object|string
     */
    protected function getTemplateByIdAndType($templateId, $type, $ignoreIsActiveState = false)
    {
        if (!ctype_digit($templateId) && !is_int($templateId)) {
            return Validator::createJsonResponse('TemplateId must be an integer', 428);
        }

        $type = TreatmentTypeService::getValidateType($type);
        if ($type instanceof JsonResponse) { return $type; }

        $template = $this->treatmentTemplateRepository->findOneBy(['type' => $type, 'id' => $templateId]);
        if ($template === null) {
            return Validator::createJsonResponse('No template of type '.$type
                .' found for id '.$templateId, 428);
        }

        if ($template->isActive() === false && !$ignoreIsActiveState) {
            return Validator::createJsonResponse('Template has already been deactivated', 428);
        }
        return $template;
    }


    /**
     * @param string|int|float $oldValue
     * @param string|int|float $newValue
     * @return string
     */
    protected function appendUpdateDescription($oldValue, $newValue)
    {
        $oldValue = $oldValue === null ? '' : $oldValue;
        $newValue = $newValue === null ? '' : $newValue;

        return $this->appendDescription($oldValue . ' => ' . $newValue);
    }


    /**
     * @param string $string
     * @return string
     */
    protected function appendDescription($string)
    {
        if ($this->actionLogDescription === null) {
            $this->actionLogDescription = '';
        }

        $prefix = '';
        if ($this->actionLogDescription !== '') {
            $prefix = ', ';
        }

        $this->actionLogDescription = $this->actionLogDescription . $prefix . $string;

        return $this->actionLogDescription;
    }
}