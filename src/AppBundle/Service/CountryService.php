<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Criteria\CountryCriteria;
use AppBundle\Entity\Country;
use AppBundle\Entity\Province;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\ProvinceOutput;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class CountryService extends DeclareControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountryCodes(Request $request)
    {
        $countries = $this->getManager()->getRepository(Country::class)
            ->getAll($this->getBaseSerializer(), $this->getCacheService());

        if ($request->query->has(Constant::CONTINENT_NAMESPACE)) {
            $continent = ucfirst($request->query->get(Constant::CONTINENT_NAMESPACE));

            if (strtolower($continent) !== Constant::ALL_NAMESPACE) {
                $countriesCollection = new ArrayCollection($countries);
                $countries = $countriesCollection
                    ->matching(CountryCriteria::byContinent($continent))
                    ->toArray()
                ;
            }
        }

        return ResultUtil::successResult($countries);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getCountries(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $retrieveCountries = $this->buildMessageObject(RequestType::RETRIEVE_COUNTRIES_ENTITY, $content, $client, $this->getUser(), $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($retrieveCountries);

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($retrieveCountries);

        return new JsonResponse($retrieveCountries, 200);
    }


    function getDutchProvinces(Request $request)
    {
        //Convert the array into an object and add the mandatory values retrieved from the database
        $provinces = $this->getManager()->getRepository(Province::class)->findDutchProvinces();
        $output = ProvinceOutput::create($provinces, true);

        return new JsonResponse($output, 200);
    }
}
