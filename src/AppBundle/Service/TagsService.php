<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;


class TagsService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getTagById(Request $request, $Id)
    {
        //validate if Id is of format: AZ123456789
        $isValidUlnFormat = Validator::verifyUlnFormat($Id);

        if(!$isValidUlnFormat){
            return new JsonResponse(
                array("errorCode" => 428,
                    "errorMessage" => "Given tagId format is invalid, supply tagId in the following format: AZ123456789"), 428);
        }

        $client = $this->getAccountOwner($request);
        $tag = $this->getManager()->getRepository(Tag::class)->findOneByString($client, $Id);

        if($tag == null) {
            return new JsonResponse(
                array("errorCode" => 400,
                    "errorMessage" => "No tag found"), 400);
        } else {
            return new JsonResponse($tag, 200);
        }
    }


    /**
     * @param Request $request
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTags(Request $request): array
    {
        return $this->createTagsOutputByRequest($request);
    }


    /**
     * @param Request $request
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createTags(Request $request): array
    {
        $this->validateAnimalsByPlainTextInputRequest($request);

        $client = $this->getAccountOwner($request);
        $this->nullCheckClient($client);

        $location = $this->getSelectedLocation($request);
        $content = RequestUtil::getContentAsArray($request);

        $ulnPartsArray = $this->getUlnPartsArrayFromPlainTextInput($content, $location->getCountryCode());
        $this->validateIfUlnsAreAlreadyUsedByAnimal($ulnPartsArray);

        $user = $this->getUser();
        $clientId = $client->getId();
        $userId = $user->getId();

        $isEntityManagerReset = $this->createTagsWithUniqueConstraintViolationExceptionCheck(
            $ulnPartsArray, $client, $location);

        if ($isEntityManagerReset) {
            $client = $this->getManager()->getRepository(Person::class)->find($clientId);
            $user = $this->getManager()->getRepository(Person::class)->find($userId);
        }

        ActionLogWriter::createTags($this->getManager(), $client, $user, $ulnPartsArray);

        return $this->createTagsOutputByRequest($request);
    }


    /**
     * @param $ulnPartsArray
     * @param Client $client
     * @param Location $location
     * @param int $loopCount
     * @param int $maxRetries
     * @param bool $hasUpdatedEntityManager
     * @return bool
     * @throws UniqueConstraintViolationException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTagsWithUniqueConstraintViolationExceptionCheck($ulnPartsArray, Client $client,
                                                                           Location $location,
                                                                           $loopCount = 1, $maxRetries = 5,
                                                                           bool $hasUpdatedEntityManager = false)
    {
        $tagRepository = $this->getManager()->getRepository(Tag::class);
        $currentTags = $tagRepository->findByUlnPartsArray($ulnPartsArray);

        $clientId = $client->getId();
        $locationId = $location->getId();

        try {
            $hasUpdatedCurrentTags = $this->updateTagsByFoundCurrentTags($client, $location, $currentTags);
            $hasInsertedNewTags = $this->insertNewTags($ulnPartsArray, $currentTags, $client, $location);

            if ($hasUpdatedCurrentTags || $hasInsertedNewTags) {
                $this->getManager()->flush();
            }

        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            SqlUtil::bumpPrimaryKeySeq($this->getConnection(), 'tag', $this->getLogger());

            $this->resetManager();

            $client = $this->getManager()->getRepository(Client::class)->find($clientId);
            $location = $this->getManager()->getRepository(Location::class)->find($locationId);

            if ($loopCount <= $maxRetries) {
                return $this->createTagsWithUniqueConstraintViolationExceptionCheck($ulnPartsArray, $client, $location,
                    $loopCount++, $maxRetries, true);
            } else {
                throw $uniqueConstraintViolationException;
            }
        }
        return $hasUpdatedEntityManager;
    }


    public function deleteTag(Request $request, Tag $tag)
    {
        $client = $this->getAccountOwner($request);
        $this->nullCheckClient($client);

        if (!$tag->getOwnerId() === $client->getId()) {
            throw new PreconditionFailedHttpException($this->translator->trans('TAG DOES NOT BELONG TO YOU'));
        }

        if ($tag->getUlnCountryCode() === \AppBundle\Enumerator\Country::NL) {
            throw new PreconditionFailedHttpException($this->translator->trans('NL TAGS CANNOT BE MANUALLY DELETED'));
        }

        $this->getManager()->remove($tag);
        $this->getManager()->flush();

        ActionLogWriter::deleteTag($this->getManager(), $client, $this->getUser(), $tag->getUln());

        return $this->createTagsOutputByRequest($request);
    }


    /**
     * @param ArrayCollection $content
     * @param string $countryCode
     * @return array
     */
    private function getUlnPartsArrayFromPlainTextInput(ArrayCollection $content, string $countryCode)
    {
        $plainTextInput = StringUtil::preparePlainTextInput($content->get(JsonInputConstant::PLAIN_TEXT_INPUT));
        $separator = $content->get(JsonInputConstant::SEPARATOR);

        $incorrectInputs = [];
        $ulnPartsArray = [];

        $parts = explode($separator, $plainTextInput);
        foreach ($parts as $part) {
            $ulnString = strtoupper(StringUtil::removeSpaces($part));

            if ($ulnString === '') {
                continue;
            }


            if (Validator::verifyUlnFormat($ulnString, false)) {
                $ulnParts = Utils::getUlnFromString($ulnString);
                $ulnCountryCode = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE];
                if ($ulnCountryCode === $countryCode) {
                    $uln = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE].$ulnParts[JsonInputConstant::ULN_NUMBER];
                    $ulnPartsArray[$uln] = $ulnParts;
                    continue;
                } else {
                    $incorrectInputs[] = trim($part) . ' '.
                        $this->translator->trans('COUNTRY CODE DOES NOT MATCH COUNTRY CODE OF UBN').
                        ' ' . $ulnCountryCode . ' ' . $this->translator->trans('SHOULD BE').' '.$countryCode;
                    continue;
                }
            }
            $incorrectInputs[] = trim($part);
        }

        if (!empty($incorrectInputs)){
            $errorMessage = $this->translator->trans("INVALID INPUT");
            throw new PreconditionFailedHttpException($errorMessage.": ".implode(',', $incorrectInputs));
        }

        return array_values($ulnPartsArray);
    }


    private function validateIfUlnsAreAlreadyUsedByAnimal(array $ulnParts)
    {
        if (empty($ulnParts)) {
            return [];
        }

        $qb = $this->getManager()->createQueryBuilder();

        $qb
            ->select('a')
            ->from (Animal::class, 'a')
        ;

        foreach ($ulnParts as $ulnPart) {
            $ulnCountryCode = $ulnPart[JsonInputConstant::ULN_COUNTRY_CODE];
            $ulnNumber = $ulnPart[JsonInputConstant::ULN_NUMBER];
            $qb->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('a.ulnCountryCode', "'".$ulnCountryCode."'"),
                    $qb->expr()->eq('a.ulnNumber', "'".$ulnNumber."'")
                )
            );
        }

        $query = $qb->getQuery();
        $query->useQueryCache(true);
        $query->setCacheable(true);

        $animals = $query->getResult();

        if (empty($animals)) {
            return;
        }

        $blockedUlns = array_map(function(Animal $animal) {
                return $animal->getUln();
            }, $animals);

        throw new PreconditionFailedHttpException($this->translateUcFirstLower(
            'THE FOLLOWING ULNS ARE ALREADY IN USE BY OTHER ANIMALS').': '.implode(', ', $blockedUlns)
        );
    }


    /**
     * @param Client $client
     * @param Location $location
     * @param array|Tag[] $currentTags
     * @return bool
     */
    private function updateTagsByFoundCurrentTags(Client $client, Location $location, $currentTags): bool
    {
        $hasUpdatedTags = false;
        $blockedTagsErrorMessage = [];
        foreach ($currentTags as $tag)
        {
            // VALIDATION

            if (
                $tag->getTagStatus() === TagStateType::ASSIGNED ||
                $tag->getTagStatus() === TagStateType::ASSIGNING ||
                $tag->getTagStatus() === TagStateType::TRANSFERRING_TO_NEW_OWNER ||
                $tag->getTagStatus() === TagStateType::REPLACING ||
                $tag->getTagStatus() === TagStateType::REPLACED ||
                $tag->getTagStatus() === TagStateType::RESERVED
            ) {
                $blockedTagsErrorMessage[] =
                    $tag->getUln() .
                    ' ['.$this->translator->trans($tag->getTagStatus()).'] '
                ;
                continue;
            }

            if (
                $tag->getTagStatus() === TagStateType::TRANSFERRING_TO_NEW_OWNER ||
                empty($tag->getTagStatus())
            ) {
                $tag->setTagStatus(TagStateType::UNASSIGNED);
            }

            if ($tag->getTagStatus() !== TagStateType::UNASSIGNED) {
                $blockedTagsErrorMessage[] = 'EXISTING TAG FOUND WITH NON STANDARD STATUS '.$tag->getUln() .
                    ' ['.$this->translator->trans($tag->getTagStatus()).'] ';
            }

            if (!empty($blockedTagsErrorMessage)) {
                continue;
            }

            if ($tag->getOwner() && $tag->getOwnerId() !== $client->getId()) {
                $blockedTagsErrorMessage[] =
                    $tag->getUln() .
                    ' ['.$this->translator->trans($tag->getTagStatus()).'] ' .
                    $this->translator->trans('IS ALREADY OWNED BY ANOTHER USER')
                ;
            }

            // UPDATING TAGS

            if (!$tag->getOwner() && !$tag->getTagStatus() === TagStateType::REPLACED) {
                $tag->setOwner($client);
                $tag->setLocation($location);
                $this->getManager()->persist($tag);
                $hasUpdatedTags = true;
            }
        }

        if (!empty($blockedTagsErrorMessage)) {
            $errorMessage = $this->translator->trans('THE FOLLOWING ULNS ARE BLOCKED FOR YOU').': ';
            throw new PreconditionFailedHttpException($errorMessage . implode('; ', $blockedTagsErrorMessage));
        }

        return $hasUpdatedTags;
    }


    /**
     * @param array $ulnPartsArray
     * @param array|Tag[] $currentTags
     * @param Client $client
     * @param Location $location
     * @return bool
     */
    private function insertNewTags(array $ulnPartsArray, array $currentTags,
                                   Client $client, Location $location): bool
    {
        $insertedNewTags = false;

        $alreadyExisting = [];

        $currentUlns = [];
        foreach ($currentTags as $currentTag) {
            $currentUlns[] = $currentTag->getUln();
        }

        foreach ($ulnPartsArray as $ulnPart) {
            $ulnCountryCode = $ulnPart[JsonInputConstant::ULN_COUNTRY_CODE];
            $ulnNumber = $ulnPart[JsonInputConstant::ULN_NUMBER];
            $newUln = $ulnCountryCode . $ulnNumber;
            if (in_array($newUln, $currentUlns)) {
                $alreadyExisting[] = $newUln;
                continue;
            }

            $newTag = new Tag();
            $newTag->setTagStatus(TagStateType::UNASSIGNED);
            $newTag->setOrderDate(new \DateTime());
            $newTag->setOrderDate(new \DateTime());
            $newTag->setOwner($client);
            $newTag->setLocation($location);
            $newTag->setUlnCountryCode($location->getCountryCode());
            $newTag->setUlnNumber($ulnNumber);
            $newTag->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($ulnNumber));

            $this->getManager()->persist($newTag);

            $insertedNewTags = true;
        }

        if (!empty($alreadyExisting)) {
            $errorMessage = $this->translator->trans('YOU ALREADY OWN TAGS WITH THE FOLLOWING ULNS');
            throw new PreconditionFailedHttpException($errorMessage.': '.implode(',', $alreadyExisting));
        }

        return $insertedNewTags;
    }


    /**
     * @param Request $request
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTagsOutputByRequest(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $this->nullCheckClient($client);

        $location = $this->getSelectedLocation($request);
        $tagStatus = $this->getTagStatusQueryParam($request);

        // For non NL locations always display all eartags of client
        $defaultIgnoreLocation = !$location->isDutchLocation();
        $ignoreLocation = RequestUtil::getBooleanQuery($request,QueryParameter::IGNORE_LOCATION, $defaultIgnoreLocation);

        return $this->createTagsOutput($client, $location, $tagStatus, $ignoreLocation);
    }


    /**
     * @param Client $client
     * @param Location $location
     * @param string $tagStatus
     * @param bool $ignoreLocation
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTagsOutput(Client $client, Location $location, string $tagStatus,
                                      bool $ignoreLocation): array
    {
        $tagRepository = $this->getManager()->getRepository(Tag::class);
        return $tagRepository->findTags($client, $location, $tagStatus, $ignoreLocation);
    }

    /**
     * @param Request $request
     * @return null|string
     */
    private function getTagStatusQueryParam(Request $request): string
    {
        return $request->query->has(Constant::STATE_NAMESPACE)
            ? $request->query->get(Constant::STATE_NAMESPACE) : TagStateType::UNASSIGNED;
    }

    /**
     * @param Request $request
     */
    private function validateAnimalsByPlainTextInputRequest(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

        if ($content === null) {
            throw new BadRequestHttpException('CONTENT IS MISSING.');
        }

        $errorMessage = '';
        $errorMessagePrefix = '';

        if ($content->get(JsonInputConstant::PLAIN_TEXT_INPUT) === null) {
            $errorMessage .= $errorMessagePrefix . $this->translateUcFirstLower('THE PLAIN_TEXT_INPUT FIELD IS MISSING.');
            $errorMessagePrefix = ' ';
        }

        if ($content->get(JsonInputConstant::SEPARATOR) === null) {
            $errorMessage .= $errorMessagePrefix . $this->translateUcFirstLower('THE SEPARATOR FIELD IS MISSING.');
        }

        if ($errorMessage !== '') {
            throw new BadRequestHttpException($errorMessage);
        }
    }
}