<?php


namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse as JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/ledger-categories")
 */
class LedgerCategoryAPIController extends APIController
{
    /**
     * Retrieve all LedgerCategories.
     *
     * ### Result Body ###
     *
     *  {
     *      "result":
     *          [
     *              {
                        "id": 1,
                        "code": "0100",
                        "description": "Goodwill",
                        "is_active": true
                    },
                    {
                        "id": 2,
                        "code": "0150",
                        "description": "Afschrijving Goodwill",
                        "is_active": true
                    }
     *          ]
     *  }
     *
     *
     * @ApiDoc(
     *   section = "Action Log",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="active_only",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="if false return inActive ledgerCategories, is true by default",
     *        "format"="?active_only=false"
     *      },
     *   },
     *   resource = true,
     *   description = "Retrieve all LedgerCategories",
     *   statusCodes={200="Returned when successful"},
     *   input="json",
     *   output="json"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getAll(Request $request)
    {
        return $this->get('AppBundle\Service\LedgerCategoryService')->getAllByRequest($request);
    }
}