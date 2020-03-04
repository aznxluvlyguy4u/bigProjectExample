<?php


namespace AppBundle\Service;


use AppBundle\Controller\ScanMeasurementsAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\Person;
use AppBundle\Entity\ScanMeasurementSet;
use AppBundle\model\request\ScanMeasurementsValues;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScanMeasurementsService extends ControllerServiceBase implements ScanMeasurementsAPIControllerInterface
{
    public static function getResult(ScanMeasurementSet $scanMeasurementSet): ScanMeasurementsValues {
        return (new ScanMeasurementsValues())->mapScanMeasurementSet($scanMeasurementSet);
    }

    function getScanMeasurements(Request $request, $animalId)
    {
        $animal = $this->getAnimal($animalId);
        $this->validateAuthorization($this->getUser(), $animal);
        if (!$animal->getScanMeasurementSet()) {
            return ResultUtil::notFound();
        }
        return self::getResult($animal->getScanMeasurementSet());
    }

    function deleteScanMeasurements(Request $request, $animalId)
    {
        $actionBy = $this->getUser();
        $animal = $this->getAnimal($animalId);

        $this->validateAuthorization($actionBy, $animal);

        if ($animal->getScanMeasurementSet()) {
            $this->getManager()->getRepository(ScanMeasurementSet::class)
                ->delete($animal->getScanMeasurementSet(), $actionBy);
            return ResultUtil::noContent();
        }

        return ResultUtil::notFound();
    }


    function modifyScanMeasurements(Request $request, $animalId)
    {
        $actionBy = $this->getUser();
        $requestBody = $this->getContent($request);
        $animal = $this->getAnimal($animalId);

        $this->validateAuthorization($actionBy, $animal);
        $this->validateRequestBody($requestBody, $animal);

        if ($animal->getScanMeasurementSet()) {
            $scanMeasurementSet = $this->edit($animal, $requestBody, $actionBy);
        } else {
            $scanMeasurementSet = $this->create($animal, $requestBody, $actionBy);
        }

        return self::getResult($scanMeasurementSet);
    }

    private function getContent(Request $request): ScanMeasurementsValues
    {
        // It is not possible to use the serializer, because it will ignore the validation rules
        $content = RequestUtil::getContentAsArrayCollection($request);
        $requestBody = new ScanMeasurementsValues();
        $requestBody->inspectorId = $content->get('inspector_id');
        $requestBody->fat1 = $content->get('fat1');
        $requestBody->fat2 = $content->get('fat2');
        $requestBody->fat3 = $content->get('fat3');
        $requestBody->scanWeight = $content->get('scan_weight');
        $requestBody->muscleThickness = $content->get('muscle_thickness');
        $requestBody->measurementDate = $content->containsKey('measurement_date') ?
            new \DateTime($content->get('measurement_date')) : null;
        return $requestBody;
    }

    private function validateAuthorization(Person $person, Animal $animal) {
        if ($person instanceof Client) {
            if (!Validator::isAnimalOfClient($animal, $person)) {
                throw new AccessDeniedHttpException();
            }
        } elseif(!($person instanceof Employee)) {
            throw new AccessDeniedHttpException();
        }
    }

    private function validateRequestBody(ScanMeasurementsValues $requestBody, Animal $animal)
    {
        // Plain input validations
        $errors = $this->getValidator()->validate($requestBody);
        Validator::throwExceptionWithFormattedErrorMessageIfHasErrors($errors, $this->translator);

        $customErrors = [];
        if ($requestBody->measurementDate < $animal->getDateOfBirth()){
            $customErrors[] = $this->translator->trans('THE EVENT DATE CANNOT BE BEFORE THE DATE OF BIRTH');
        }

        if ($requestBody->inspectorId) {
            $inspector = $this->getManager()->getRepository(Inspector::class)->find($requestBody->inspectorId);
            if (!$inspector) {
                $customErrors[] = $this->translator->trans('NO ACTIVE INSPECTOR FOUND FOR GIVEN ID')
                    .': '.$requestBody->inspectorId;
            }
        }

        if (!empty($customErrors)) {
            throw New BadRequestHttpException(implode('| ', $customErrors));
        }
    }


    private function getAnimal($animalId): Animal {
        $animal = $this->getManager()->getRepository(Animal::class)->find(intval($animalId));
        if (!$animal || !($animal instanceof Animal)) { throw new NotFoundHttpException("No Animal Found with animalId: ".$animalId); }
        return $animal;
    }


    /**
     * @param  Animal  $animal
     * @param  ScanMeasurementsValues  $values
     * @param  Person  $actionBy
     * @return ScanMeasurementSet
     */
    private function edit(Animal $animal, ScanMeasurementsValues $values, Person $actionBy): ScanMeasurementSet
    {
        $set = $animal->getScanMeasurementSet();
        if ($values->hasEqualValues($set)) {
            return $set;
        }

        return $this->getManager()->getRepository(ScanMeasurementSet::class)
            ->edit($set, $values, $actionBy);
    }


    /**
     * @param  Animal  $animal
     * @param  ScanMeasurementsValues  $values
     * @param  Person  $actionBy
     * @return ScanMeasurementSet
     */
    private function create(Animal $animal, ScanMeasurementsValues $values, Person $actionBy): ScanMeasurementSet
    {
        return $this->getManager()->getRepository(ScanMeasurementSet::class)
            ->create($animal, $values, $actionBy);
    }
}
