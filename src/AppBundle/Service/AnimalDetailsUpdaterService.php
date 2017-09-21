<?php


namespace AppBundle\Service;


use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Employee;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimalDetailsUpdaterService
{
    /** @var ObjectManager|EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var CacheService */
    private $cacheService;
    /** @var UserService */
    private $userService;

    /** @var AnimalRepository */
    private $animalRepository;

    /** @var string */
    private $actionLogMessage;
    /** @var Request */
    private $request;

    /**
     * AnimalDetailsUpdaterService constructor.
     * @param EntityManagerInterface $em
     * @param CacheService $cacheService
     * @param UserService $userService
     */
    public function __construct(EntityManagerInterface $em, CacheService $cacheService, UserService $userService)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->cacheService = $cacheService;
        $this->userService = $userService;

        $this->animalRepository = $this->em->getRepository(Animal::class);
    }


    /**
     * @param Request $request
     * @param $ulnString
     * @return JsonResponse
     */
    public function update(Request $request, $ulnString)
    {
        $this->request = $request;

        //Get content to array
        $content = RequestUtil::getContentAsArray($request);
        $animal = $this->animalRepository->findAnimalByUlnString($ulnString);

        if($animal === null) {
            return ResultUtil::errorResult("For this account, no animal was found with uln: " . $ulnString, Response::HTTP_NO_CONTENT);
        }

        $user = $this->userService->getUser();

        if(!$user instanceof Employee) {
            $animalOwner = $animal->getOwner();
            if ($animalOwner !== $user && $animalOwner !== $user->getEmployer()) {
                $message = 'Dit dier is op dit moment niet in uw bezit en u bent niet door de huidige eigenaar geautoriseerd,'
                    .' dus het is niet toegestaan voor u om de gegevens aan te passen.';
                return ResultUtil::errorResult($message, Response::HTTP_UNAUTHORIZED);
            }
        }

        $this->updateValues($animal, $content);

        //Clear cache for this location, to reflect changes on the livestock
        $this->cacheService->clearLivestockCacheForLocation(null, $animal);

        $output = AnimalDetailsOutput::create($this->em, $animal);

        return new JsonResponse($output, 200);
    }


    /**
     * @param Animal $animal
     * @param Collection $content
     * @return Animal
     */
    private function updateValues($animal, Collection $content)
    {
        if(!($animal instanceof Animal)){ return $animal; }

        //Keep track if any changes were made
        $anyValueWasUpdated = false;

        $this->clearActionLogMessage();

        //Collar color & number
        if($content->containsKey('collar')) {
            $collar = $content->get('collar');
            $newCollarNumber = ArrayUtil::get('number',$collar);
            $newCollarColor = ArrayUtil::get('color',$collar);

            $oldCollarColor = $animal->getCollarColor();
            $oldCollarNumber = $animal->getCollarNumber();

            if($oldCollarColor != $newCollarColor) {
                $animal->setCollarColor($newCollarColor);
                $anyValueWasUpdated = true;

                $this->updateActionLogMessage('halsbandkleur', $oldCollarColor, $newCollarColor);
            }

            if($oldCollarNumber != $newCollarNumber) {
                $animal->setCollarNumber($newCollarNumber);
                $anyValueWasUpdated = true;

                $this->updateActionLogMessage('halsbandnr', $oldCollarNumber, $newCollarNumber);
            }

        }

        //Only update animal in database if any values were actually updated
        if($anyValueWasUpdated) {
            $this->em->persist($animal);
            $this->em->flush();

            $this->saveActionLogMessage();
        }

        //TODO if breedCode was updated toggle $isBreedCodeUpdated boolean to true
        $isBreedCodeUpdated = false;
        if($isBreedCodeUpdated) {
            //Update heterosis and recombination values of parent and children if breedCode of parent was changed
            GeneDiversityUpdater::updateByParentId($this->conn, $animal->getId());
        }

        return $animal;
    }



    private function clearActionLogMessage()
    {
        $this->actionLogMessage = '';
    }


    /**
     * @param $type
     * @param $oldValue
     * @param $newValue
     */
    private function updateActionLogMessage($type, $oldValue, $newValue)
    {
        if ($oldValue !== $newValue) {
            $prefix = $this->actionLogMessage === '' ? '' : ', ';
            $this->actionLogMessage = $this->actionLogMessage . $prefix . $type . ': '.$oldValue.' => '.$newValue;
        }
    }


    private function saveActionLogMessage()
    {
        ActionLogWriter::editAnimalDetails($this->em, $this->userService->getAccountOwner($this->request),
                                           $this->userService->getUser(), $this->actionLogMessage,true);
    }
}