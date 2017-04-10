<?php


namespace AppBundle\Utilities;


use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestUtil
{
    /**
     * @param Request $request
     * @return ArrayCollection
     */
    public static function getContentAsArray(Request $request)
    {
        $content = $request->getContent();

        if(empty($content)){
            throw new BadRequestHttpException("Content is empty");
        }

        $array = json_decode($content, true);
        if($array == null) {
            return null;
        }

        return new ArrayCollection($array);
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
            if(!TimeUtil::isFormatDDMMYYYY($queryValue)) {
                if (!TimeUtil::isFormatYYYYMMDD($queryValue)) {
                    return $nullResult;
                }
            }
        }
        return new \DateTime($queryValue);
    }
}