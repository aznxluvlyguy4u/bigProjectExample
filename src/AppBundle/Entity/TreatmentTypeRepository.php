<?php

namespace AppBundle\Entity;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Service\TreatmentTypeService;

class TreatmentTypeRepository extends BaseRepository
{
    /**
     * @param bool $activeOnly
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function findByQueries($activeOnly = false, $type = null)
    {
        $criteria = [];

        if ($activeOnly) { $criteria['isActive'] = true; }

        if ($type !== null) {
            $validatedType = TreatmentTypeService::getValidateType($type);
            if ($validatedType instanceof JsonResponse) { throw new \Exception($validatedType->getContent()); }

            $criteria['type'] = $validatedType;
        }

        return $this->findBy($criteria,  ['type' => 'ASC','description' => 'ASC']);
    }


    /**
     * @param string $type
     * @param string $description
     * @return null|TreatmentType|object
     * @throws \Exception
     */
    public function findActiveOneByTypeAndDescription($type, $description)
    {
        $criteria = [
            'isActive' => true,
            'description' => $description,
        ];

        if ($type !== null) {
            $validatedType = TreatmentTypeService::getValidateType($type);
            if ($validatedType instanceof JsonResponse) { throw new \Exception($validatedType->getContent()); }

            $criteria['type'] = $validatedType;
        }

        return $this->findOneBy($criteria);
    }


    /**
     * @param string $type
     * @param string $description
     * @return null|TreatmentType|object
     * @throws \Exception
     */
    public function findOneByTypeAndDescription($type, $description)
    {
        $criteria = [
            'description' => $description,
        ];

        if ($type !== null) {
            $validatedType = TreatmentTypeService::getValidateType($type);
            if ($validatedType instanceof JsonResponse) { throw new \Exception($validatedType->getContent()); }

            $criteria['type'] = $validatedType;
        }

        return $this->findOneBy($criteria);
    }


    /**
     * @return null|object|TreatmentType
     * @throws \Exception
     */
    public function findOpenDescriptionType()
    {
        $treatmentType = $this->findOneBy(
            [
                'isActive' => true,
                'type' => TreatmentTypeOption::INDIVIDUAL,
                'description' => TreatmentType::OPEN_OPTION_DESCRIPTION,
            ]);

        if ($treatmentType === null) {
            throw new \Exception('There should be at least one ACTIVE TreatmentType in the database with '
            .' type = '.TreatmentTypeOption::INDIVIDUAL.' and description = '.TreatmentType::OPEN_OPTION_DESCRIPTION, 428);
        }

        return $treatmentType;
    }
}
