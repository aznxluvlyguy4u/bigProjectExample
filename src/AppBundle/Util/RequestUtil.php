<?php


namespace AppBundle\Util;


use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestUtil
{
    /**
     * @param Request $request
     * @param bool $allowEmptyContent
     * @param null $emptyContentResponse
     * @return ArrayCollection
     */
    public static function getContentAsArray(Request $request, $allowEmptyContent = false, $emptyContentResponse = null)
    {
        $content = $request->getContent();

        if(empty($content)){
            if ($allowEmptyContent) {
                return $emptyContentResponse;
            } else {
                throw new BadRequestHttpException("Content is empty");
            }
        }

        return new ArrayCollection(json_decode($content, true));
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
     * @param string $queryParameter
     * @param mixed $nullResult
     * @return \DateTime|null
     */
    public static function getDateQuery(Request $request, $queryParameter, $nullResult = null)
    {
        $queryValue = $request->query->get($queryParameter);
        if($queryValue == null) {
            return $nullResult;
        } else {
            if(!DateUtil::isFormatDDMMYYYY($queryValue)) {
                if (!DateUtil::isFormatYYYYMMDD($queryValue)) {
                    return $nullResult;
                }
            }
        }
        return new \DateTime($queryValue);
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
}