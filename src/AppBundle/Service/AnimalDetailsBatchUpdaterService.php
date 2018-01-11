<?php


namespace AppBundle\Service;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimalDetailsBatchUpdaterService extends ControllerServiceBase
{
    public function updateAnimalDetails(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

        $animals = $content->get(JsonInputConstant::ANIMALS);

        $ids = [];
        foreach ($animals as $animal) {
            $animalId = ArrayUtil::get('id', $animal);
            if (is_int($animalId)) {
                $ids[] = $animalId;
            } elseif ($animalId !== null) {
                throw new \Exception("Animal 'id' is missing");
            }
        }

        try {
            $animals = $this->getManager()->getRepository(Animal::class)->findByIds($ids);
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $serializedAnimalsOutput = AnimalService::getSerializedAnimalsInBatchEditFormat($this, $animals);

        return ResultUtil::successResult([
            JsonInputConstant::ANIMALS => $serializedAnimalsOutput[JsonInputConstant::ANIMALS]
        ]);
    }
}