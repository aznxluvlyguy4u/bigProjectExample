<?php


namespace AppBundle\Util;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class RequestUtil
{
    const PAGE_SIZE_DEFAULT = 10;
    const PAGE_SIZE_MAX = 25;

    /**
     * @param Request $request
     * @param bool $allowEmptyContent
     * @param $emptyContentResponse
     * @param bool $isArrayCollection
     * @return array|ArrayCollection
     */
    private static function getContentBase(Request $request, bool $allowEmptyContent, $emptyContentResponse,
        bool $isArrayCollection)
    {
        $content = $request->getContent();

        if(empty($content)){
            if ($allowEmptyContent) {
                return $emptyContentResponse;
            } else {
                throw new BadRequestHttpException("Content is empty");
            }
        }

        $decodedJson = json_decode($content, true);
        if ($decodedJson === null || $decodedJson === false) {
            throw new BadRequestHttpException();
        }

        if ($isArrayCollection) {
            return new ArrayCollection($decodedJson);
        }
        return $decodedJson;
    }

    /**
     * @param Request $request
     * @param bool $allowEmptyContent
     * @param null $emptyContentResponse
     * @return array
     */
    public static function getContentAsArray(Request $request, $allowEmptyContent = false, $emptyContentResponse = null)
    {
        return self::getContentBase($request, $allowEmptyContent, $emptyContentResponse, false);
    }

    /**
     * @param Request $request
     * @param bool $allowEmptyContent
     * @param null $emptyContentResponse
     * @return ArrayCollection
     */
    public static function getContentAsArrayCollection(Request $request, $allowEmptyContent = false, $emptyContentResponse = null)
    {
        return self::getContentBase($request, $allowEmptyContent, $emptyContentResponse, true);
    }


    /**
     * @param ArrayCollection $collection
     * @return string
     */
    public static function revertToJson(ArrayCollection $collection)
    {
        return json_encode($collection->toArray());
    }


    /**
     * @param Request $request
     * @param string $queryParameter
     * @param boolean $nullResult
     * @return bool
     */
    public static function getBooleanQuery(Request $request, $queryParameter, $nullResult = false)
    {
        return $request->query->get($queryParameter) ?
            boolval(filter_var($request->query->get($queryParameter), FILTER_VALIDATE_BOOLEAN)) : $nullResult;
    }


    /**
     * @param Request $request
     * @param $queryParameter
     * @param null $nullResult
     * @param bool $validateNotEmpty
     * @param bool $validateFormat
     * @return \DateTime|null
     * @throws \Exception
     */
    public static function getDateQuery(Request $request, $queryParameter, $nullResult = null,
                                        bool $validateNotEmpty = false,
                                        bool $validateFormat = false
    )
    {
        $queryValue = $request->query->get($queryParameter);
        $isEmptyValue = false;

        if($queryValue == null) {
            $isEmptyValue = true;
        } else {
            if(!DateUtil::isFormatDDMMYYYY($queryValue) && !DateUtil::isFormatYYYYMMDD($queryValue)) {
                if ($validateFormat) {
                    throw new BadRequestHttpException($queryParameter . " has an invalid format: ".$queryValue);
                }
                $isEmptyValue = true;
            }
        }

        if ($isEmptyValue) {
            if ($validateNotEmpty) {
                throw new BadRequestHttpException($queryParameter . " cannot be empty");
            }
            return $nullResult;
        }

        return new \DateTime($queryValue);
    }


    /**
     * @param Request $request
     * @return int|null
     */
    public static function getPageNumber(Request $request): ?int
    {
        $pageNumber = self::getIntegerQuery($request, 'page', 1);
        return $pageNumber < 1 ? 1: $pageNumber;
    }


    /**
     * @param Request $request
     * @param int|null $pageSize
     * @return int|null
     */
    public static function getPageSize(Request $request, ?int $pageSize = self::PAGE_SIZE_DEFAULT): ?int
    {
        $pageSize = self::getIntegerQuery($request, 'pageSize', $pageSize);
        if (intval($pageSize) && (
                self::PAGE_SIZE_MAX < $pageSize ||
                $pageSize < 1
            )) {
            throw new BadRequestHttpException('The pageSize must be at least 1 and at most '.self::PAGE_SIZE_MAX);
        }
        return $pageSize;
    }


    /**
     * @param Request $request
     * @param string $queryParameter
     * @param null|mixed $nullResult
     * @return int|null
     */
    public static function getIntegerQuery(Request $request, $queryParameter, $nullResult = null)
    {
        $queryValue = $request->query->get($queryParameter);

        if($queryValue == null) {
            return $nullResult;

        } elseif(is_int($queryValue) || ctype_digit($queryValue)) {
            return intval($queryValue);
        }
        return $nullResult;
    }


    /**
     * @param array $keys
     * @param ArrayCollection|array $content
     * @return JsonResponse|bool
     */
    public static function contentContainsNecessaryKeys(array $keys, $content)
    {
        if ($content instanceof ArrayCollection) {
            $content = $content->toArray();
        }

        $missingKeys = [];
        foreach ($keys as $key) {
            if (!key_exists($key, $content)) {
                $missingKeys[] = $key;
            }
        }

        if (count($missingKeys) === 0) { return true; }

        $keyLabel = count($missingKeys) > 1 ? 'keys are' : 'key is';
        $errorMessage = 'The following '.$keyLabel.' missing in the json body: '
            . implode(', ', $missingKeys);

        return ResultUtil::errorResult($errorMessage, 428);
    }


    /**
     * Content must contain at least one of the keys in the given set.
     *
     * @param array $keysSet
     * @param ArrayCollection|array $content
     * @return JsonResponse|bool
     */
    public static function contentContainsAtLeastOneKey($keysSet, $content)
    {
        $keysSetCount = count($keysSet);
        if ($keysSetCount === 0) { return true; }

        if ($content instanceof ArrayCollection) {
            $content = $content->toArray();
        }

        $containsAtLeastOneKeyInSet = false;
        foreach ($keysSet as $key) {
            if (key_exists($key, $content)) {
                $containsAtLeastOneKeyInSet = true;
                break;
            }
        }

        if ($containsAtLeastOneKeyInSet) { return true; }


        $keyLabel = $keysSetCount > 1 ? 'one of the following keys' : 'the following key';
        $errorMessage = 'At least include '.$keyLabel.' in the json body: '
            . implode(', ', $keysSet);

        return ResultUtil::errorResult($errorMessage, 428);
    }


    /**
     * @param ArrayCollection $content
     * @return bool
     */
    public static function isMultiEdit(ArrayCollection $content)
    {
        return $content->get(JsonInputConstant::IS_MULTI_EDIT) === true;
    }


    /**
     * @param ArrayCollection $content
     * @param string $dateKey
     * @return \DateTime
     */
    public static function getDateTimeFromContent(ArrayCollection $content, $dateKey)
    {
        $departDateString = $content->get($dateKey);
        if (empty($departDateString)) {
            throw new PreconditionFailedHttpException('Missing key: '.$dateKey);
        }
        return new \DateTime($departDateString);
    }
}
