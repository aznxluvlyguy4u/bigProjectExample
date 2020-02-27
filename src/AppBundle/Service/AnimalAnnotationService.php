<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalAnnotation;
use AppBundle\Entity\AnimalAnnotationRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\model\request\AnimalAnnotationRequest;
use AppBundle\Util\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnimalAnnotationService extends ControllerServiceBase
{
    public function getAnnotations(Request $request, $idOrUlnString) {
        $animal = $this->findAnimal($idOrUlnString);
        $animalId = $animal->getId();

        $actionBy = $this->getUser();

        switch (true) {
            case $actionBy instanceof Employee:
                $annotations = $this->repository()->findByAnimalId($animalId);
                return $this->getDecodedJson($annotations);
            case $actionBy instanceof Client:
                $annotation = $this->findAnnotationOfClient($request, $animalId);
                return $this->getDecodedJson($annotation);
            default:
                throw new AccessDeniedHttpException();
        }
    }

    public function editAnnotation(Request $request, $idOrUlnString) {
        $actionBy = $this->getUser();
        $this->validatePersonIsClient($actionBy);

        $animal = $this->findAnimal($idOrUlnString);

        $location = $this->getSelectedLocation($request);
        $company = $location->getCompany();

        $this->validateIfUserOwnsAnimal($animal, $actionBy);

        /** @var AnimalAnnotationRequest $annotationRequest */
        $annotationRequest = $this->getBaseSerializer()->denormalizeRequestContent($request, AnimalAnnotationRequest::class);
        $newBody = $annotationRequest->getBody();

        $currentAnnotation = $this->findAnnotationOfClient($request, $animal->getId());

        if ($currentAnnotation) {
            if (empty($newBody)) {
                $this->getManager()->remove($currentAnnotation);
                $this->getManager()->flush();
                return null;
            }

            if ($currentAnnotation->getBody() !== $newBody) {
                $currentAnnotation
                    ->setBody($newBody)
                    ->refreshUpdatedAt()
                    ->setActionBy($actionBy)
                ;
                $this->getManager()->persist($currentAnnotation);
                $this->getManager()->flush();
            }

            return $this->getDecodedJson($currentAnnotation);
        }

        $newAnnotation = (new AnimalAnnotation())
            ->setBody($newBody)
            ->setAnimal($animal)
            ->setActionBy($actionBy)
            ->setLocation($location)
            ->setCompany($company)
        ;
        $this->getManager()->persist($newAnnotation);
        $this->getManager()->flush();

        return $this->getDecodedJson($newAnnotation);
    }

    private function findAnimal($idOrUlnString): Animal {
        $animal = $this->getManager()->getRepository(Animal::class)->findAnimalByIdOrUln($idOrUlnString);
        if (!$animal) {
            throw new NotFoundHttpException($this->translateUcFirstLower('NO ANIMAL WAS NOT FOUND FOR ID') . ': '.$idOrUlnString);
        }
        return $animal;
    }


    private function validatePersonIsClient(Person $actionBy) {
        if (!($actionBy instanceof Client)) {
            throw new AccessDeniedHttpException('Only allowed for users of type Client');
        }
    }

    /**
     * @param  Request  $request
     * @param  int  $animalId
     * @return AnimalAnnotation|null
     */
    private function findAnnotationOfClient(Request $request, int $animalId): ?AnimalAnnotation {
        $companyId = $this->getSelectedLocation($request)->getCompany()->getId();
        return $this->repository()->findByAnimalIdAndCompanyId($animalId, $companyId);
    }


    private function repository(): AnimalAnnotationRepository {
        return $this->getManager()->getRepository(AnimalAnnotation::class);
    }

    private function getDecodedJson($object) {
        return $this->getBaseSerializer()->getDecodedJson($object, JmsGroup::ANIMAL_ANNOTATIONS);
    }

    private function validateIfUserOwnsAnimal(Animal $animal, Client $actionBy) {
        if (!$animal->getLocation()) {
            throw new AccessDeniedHttpException('Animal has no current location');
        }

        if (!Validator::isAnimalOfClient($animal, $actionBy)) {
            throw new AccessDeniedHttpException('Animal does not belong to your company');
        }
    }
}
