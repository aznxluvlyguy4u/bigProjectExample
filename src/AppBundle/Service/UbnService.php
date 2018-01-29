<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Entity\Processor;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\ProcessorOutput;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;


class UbnService extends DeclareControllerServiceBase
{
    const ALL_UBNS_CACHE_ID_ = 'ALL_UBNS_CACHE_ID_';

    /** @var array */
    private static $allUbnCacheIds = [];

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getUBNDetails(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_UBN_DETAILS_ENTITY, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject);

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($messageObject);

        return new JsonResponse($messageObject, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUbnProcessors(Request $request)
    {
        $processors = $this->getManager()->getRepository(Processor::class)->findAll();
        $includeNames = true;
        $output = ProcessorOutput::create($processors, $includeNames);
        return new JsonResponse($output, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request)
    {
        if (!($this->getUser() instanceof Employee || $this->getUser() instanceof VwaEmployee))
        { return ResultUtil::unauthorized(); }

        $activeOnly = RequestUtil::getBooleanQuery($request,QueryParameter::ACTIVE_ONLY,true);
        $includeGhostLoginData = RequestUtil::getBooleanQuery($request,QueryParameter::INCLUDE_GHOST_LOGIN_DATA,false);

        $filter = self::getUbnRepositoryFilter($activeOnly);
        $jmsGroups = self::getUbnJmsGroups($includeGhostLoginData);

        $cacheId = self::getUbnCacheId($activeOnly, $includeGhostLoginData);

        if ($this->getCacheService()->isHit($cacheId)) {
            $output = $this->getCacheService()->getItem($cacheId);
        } else {
            $ubns = $this->getManager()->getRepository(Location::class)->findBy($filter, ['ubn' => 'ASC']);
            $output = $this->getBaseSerializer()->getDecodedJson($ubns, $jmsGroups);
            $this->getCacheService()->set($cacheId, $output);
        }

        return ResultUtil::successResult($output);
    }


    /**
     * @param boolean $activeOnly
     * @return array
     */
    private static function getUbnRepositoryFilter($activeOnly)
    {
        return $activeOnly ? ['isActive' => true] : [];
    }


    /**
     * @param $includeGhostLoginData
     * @return array
     */
    private static function getUbnJmsGroups($includeGhostLoginData)
    {
        return $includeGhostLoginData ? [JmsGroup::MINIMAL, JmsGroup::GHOST_LOGIN] : [JmsGroup::MINIMAL];
    }


    /**
     * @param boolean $activeOnly
     * @param boolean $includeGhostLoginData
     * @return string
     */
    private static function getUbnCacheId($activeOnly, $includeGhostLoginData)
    {
        $filter = self::getUbnRepositoryFilter($activeOnly);
        $jmsGroups = self::getUbnJmsGroups($includeGhostLoginData);
        return self::ALL_UBNS_CACHE_ID_ . CacheService::getJmsGroupsSuffix($jmsGroups) . CacheService::getFilterSuffix($filter);
    }


    /**
     * @return array
     */
    public static function getAllUbnCacheIds()
    {
        if (count(self::$allUbnCacheIds) > 0) {
            return self::$allUbnCacheIds;
        }

        $boolValOptions = [true, false];

        foreach ($boolValOptions as $activeOnly) {
            foreach ($boolValOptions as $includeGhostLoginData) {
                self::$allUbnCacheIds[] = self::getUbnCacheId($activeOnly, $includeGhostLoginData);
            }
        }

        return self::$allUbnCacheIds;
    }

}