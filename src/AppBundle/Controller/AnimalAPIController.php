<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Service\AnimalDetailsBatchUpdaterService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/api/v1/animals")
 */
class AnimalAPIController extends APIController implements AnimalAPIControllerInterface {

  /**
   * Retrieve a list of animals. Animal-types are: {ram, ewe, neuter}
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *      {
   *        "name"="type",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" animal-type to retrieve: ram, ewe, neuter",
   *        "format"="?type=animal-type"
   *      },
   *      {
   *        "name"="alive",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="animal life-state to retrieve: true, false",
   *        "format"="?alive=live-state"
   *      },
   *   },
   *   resource = true,
   *   description = "Retrieve a list of animals"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getAllAnimalsByTypeOrState(Request $request)
  {
      return $this->get('app.animal')->getAllAnimalsByTypeOrState($request);
  }


    /**
     * Retrieve a list of animals, by a list of ulns as plain text
     *
     * @ApiDoc(
     *   section = "Animals",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="plain_text_input",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="input type (currently the false path is not implemented), true by default",
     *        "format"="?plain_text_input=true"
     *      }
     *   },
     *   resource = true,
     *   description = "Retrieve a list of animals by plain text input"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function getAnimals(Request $request)
    {
        return $this->get('app.animal')->getAnimals($request);
    }

    /**
     * Create an animal
     *
     * @ApiDoc(
     *   section = "Animals",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="plain_text_input",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="input type (currently the false path is not implemented), true by default",
     *        "format"="?plain_text_input=true"
     *      }
     *   },
     *   resource = true,
     *   description = "Retrieve a list of animals by plain text input"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/create")
     * @Method("POST")
     */
    public function createAnimal(Request $request)
    {
        return $this->get('app.animal')->createAnimal($request);
    }

    /**
     * Find an animal
     *
     * @ApiDoc(
     *   section = "Animals",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="plain_text_input",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="input type (currently the false path is not implemented), true by default",
     *        "format"="?plain_text_input=true"
     *      }
     *   },
     *   resource = true,
     *   description = "Retrieve a list of animals by plain text input"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/find")
     * @Method("POST")
     */
    public function findAnimal(Request $request)
    {
        return $this->get('app.animal')->findAnimal($request);
    }

  /**
   * Retrieve an animal, found by it's ULN. For example NL100029511721
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve an Animal by given ULN"
   * )
   * @param Request $request the request object
   * @param $uln
   * @return JsonResponse
   * @Route("/{uln}")
   * @Method("GET")
   */
  public function getAnimalById(Request $request, $uln)
  {
      return $this->get('app.animal')->getAnimalById($request, $uln);
  }

  /**
   * Retrieve all alive, on-location, animals belonging to the given UBN.
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *      {
   *        "name"="gender",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="filter output by gender (male, female, neuter), null by default",
   *        "format"="?gender=female"
   *      },
   *      {
   *        "name"="type",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="options: ewes_with_last_mate, last_weight, null by default",
   *        "format"="?type=ewes_with_last_mate"
   *      }
   *   },
   *   resource = true,
   *   description = " Retrieve all alive, on-location, animals belonging to the given UBN"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-livestock")
   * @Method("GET")
   */
  public function getLiveStock(Request $request)
  {
      return $this->get('app.animal')->getLiveStock($request);
  }



  /**
   * Retrieve all historic animals,dead or alive, that ever resided on the given UBN.
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *      {
   *        "name"="gender",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="filter output by gender (male, female, neuter), null by default",
   *        "format"="?gender=female"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve all historic animals,dead or alive, that ever resided on the given UBN"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-historic-livestock")
   * @Method("GET")
   */
  public function getHistoricLiveStock(Request $request)
  {
      return $this->get('app.animal')->getHistoricLiveStock($request);
  }
  
  /**
   * Retrieve all alive rams in the NSFO database
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="Retrieve all alive rams in the NSFO database"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve all alive rams in the NSFO database"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-rams")
   * @Method("GET")
   */
  public function getAllRams(Request $request)
  {
      return $this->get('app.animal')->getAllRams($request);
  }
  
  /**
   * Create a RetrieveAnimal request
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a RetrieveAnimals request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-sync")
   * @Method("POST")
   */
  public function createRetrieveAnimals(Request $request)
  {
      return $this->get('app.animal')->createRetrieveAnimals($request);
  }

  /**
   * Create RetrieveAnimal requests for all clients.
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Create RetrieveAnimal requests for all clients"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-sync-all")
   * @Method("POST")
   */
  public function createRetrieveAnimalsForAllLocations(Request $request)
  {
      return $this->get('app.animal')->createRetrieveAnimalsForAllLocations($request);
  }


    /**
     * Get latest RVO leading RetrieveAnimal request for logged in ubn.
     *
     * @ApiDoc(
     *   section = "Animals",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get latest RVO leading RetrieveAnimal request for logged in ubn"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-sync/rvo-leading")
     * @Method("GET")
     */
  public function getLatestRvoLeadingRetrieveAnimals(Request $request)
  {
      return $this->get('app.animal')->getLatestRvoLeadingRetrieveAnimals($request);
  }


  /**
   * Create a RetrieveAnimalDetails request
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a RetrieveAnimals request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-details")
   * @Method("POST")
   */
  function createAnimalDetails(Request $request)
  {
      return $this->get('app.animal')->createAnimalDetails($request);
  }

  /**
   * Update animal details
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Update animal details"
   * )
   * @param Request $request the request object
   * @param string $ulnString
   * @return JsonResponse
   * @Route("-details/{ulnString}")
   * @Method("PUT")
   */
  function updateAnimalDetails(Request $request, $ulnString)
  {
      return $this->get('app.animal.details.updater')->updateAnimalDetails($request, $ulnString);
  }


    /**
     * Batch update animal details
     *
     * @ApiDoc(
     *   section = "Animals",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Batch update animal details"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-details")
     * @Method("PUT")
     */
    function batchUpdateAnimalDetails(Request $request)
    {
        return $this->get(AnimalDetailsBatchUpdaterService::class)->updateAnimalDetails($request);
    }


  /**
   * Get Animal Details by ULN. For example NL100029511721
   *
   * @ApiDoc(
   *   section = "Animals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *      {
   *        "name"="minimal_output",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="set to true to return only the most essential data, it is false by default",
   *        "format"="?minimal_output=true"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve an Animal by ULN"
   * )
   * @param Request $request the request object
   * @param string $ulnString
   * @return JsonResponse
   * @Route("-details/{ulnString}")
   * @Method("GET")
   * @throws \Exception
   */
  public function getAnimalDetailsByUln(Request $request, $ulnString)
  {
      return $this->get('app.animal')->getAnimalDetailsByUln($request, $ulnString);
  }


    /**
     * Get children of animal by ULN. For example NL100029511721
     *
     * @ApiDoc(
     *   section = "Animals",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get children of animal by ULN. For example NL100029511721"
     * )
     * @param Request $request the request object
     * @param string $ulnString
     * @return JsonResponse
     * @Route("-details/{ulnString}/children")
     * @Method("GET")
     * @throws \Exception
     */
    public function getChildrenByUln(Request $request, $ulnString)
    {
        return $this->get('app.animal')->getChildrenByUln($request, $ulnString);
    }


	/**
	 *
	 * Change the gender of an Animal for a given ULN. For example NL100029511721
	 *
	 * @ApiDoc(
	 *   section = "Animals",
	 *   requirements={
	 *     {
	 *       "name"="AccessToken",
	 *       "dataType"="string",
	 *       "requirement"="",
	 *       "description"="A valid accesstoken belonging to the user that is registered with the API"
	 *     }
	 *   },
	 *   resource = true,
	 *   description = "Change the gender of an Animal for a given ULN"
	 * )
	 *
	 * @param Request $request the request object
	 * @return jsonResponse
	 * @Route("-gender")
	 * @Method("POST")
	 */
  public function changeGenderOfUln(Request $request)
  {
      return $this->get('app.animal')->changeGenderOfUln($request);
  }

	/**
	 *
	 * Change the nickname of an Animal for a given id.
	 *
	 * @ApiDoc(
	 *   section = "Animals",
	 *   requirements={
	 *     {
	 *       "name"="AccessToken",
	 *       "dataType"="string",
	 *       "requirement"="",
	 *       "description"="A valid accesstoken belonging to the user that is registered with the API"
	 *     }
	 *   },
	 *   resource = true,
	 *   description = "Change the nickname of an Animal for a given id"
	 * )
	 *
	 * @param Request $request the request object
	 * @param Animal $animal
	 * @return jsonResponse
	 * @Route("-nickname/{animal}")
	 * @Method("PUT")
	 */
	public function changeNicknameOfAnimalById(Request $request, Animal $animal)
	{
			return $this->get('app.animal')->changeNicknameOfAnimal($request, $animal);
	}

}
