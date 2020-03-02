<?php


namespace AppBundle\SqlView;


use AppBundle\SqlView\Repository\SqlViewRepositoryInterface;
use AppBundle\SqlView\Repository\ViewAnimalHistoricLocationsRepository;
use AppBundle\SqlView\Repository\ViewAnimalIsPublicDetailsRepository;
use AppBundle\SqlView\Repository\ViewAnimalLivestockOverviewDetailsRepository;
use AppBundle\SqlView\Repository\ViewBreedValueMaxGenerationDateRepository;
use AppBundle\SqlView\Repository\ViewLitterDetailsRepository;
use AppBundle\SqlView\Repository\ViewLocationDetailsRepository;
use AppBundle\SqlView\Repository\ViewMinimalParentDetailsRepository;
use AppBundle\SqlView\Repository\ViewPedigreeRegisterAbbreviationRepository;
use AppBundle\SqlView\Repository\ViewPersonFullNameRepository;
use AppBundle\SqlView\View\ViewAnimalHistoricLocations;
use AppBundle\SqlView\View\ViewAnimalIsPublicDetails;
use AppBundle\SqlView\View\ViewAnimalLivestockOverviewDetails;
use AppBundle\SqlView\View\ViewBreedValueMaxGenerationDate;
use AppBundle\SqlView\View\ViewLitterDetails;
use AppBundle\SqlView\View\ViewLocationDetails;
use AppBundle\SqlView\View\ViewMinimalParentDetails;
use AppBundle\SqlView\View\ViewPedigreeRegisterAbbreviation;
use AppBundle\SqlView\View\ViewPersonFullName;
use Symfony\Component\HttpFoundation\Response;


class SqlViewManager implements SqlViewManagerInterface
{
    /** @var array */
    private $repositories;

    public function __construct(
        ViewAnimalLivestockOverviewDetailsRepository $animalLivestockOverviewDetailsRepository,
        ViewAnimalHistoricLocationsRepository $animalHistoricLocationsRepository,
        ViewAnimalIsPublicDetailsRepository $animalIsPublicDetailsRepository,
        ViewLitterDetailsRepository $viewLitterDetailsRepository,
        ViewLocationDetailsRepository $viewLocationDetailsRepository,
        ViewMinimalParentDetailsRepository $viewMinimalParentDetailsRepository,
        ViewPedigreeRegisterAbbreviationRepository $viewPedigreeRegisterAbbreviationRepository,
        ViewPersonFullNameRepository $viewPersonFullNameRepository,
        ViewBreedValueMaxGenerationDateRepository $viewBreedValueMaxGenerationDateRepository
    )
    {
        $this->repositories[ViewAnimalLivestockOverviewDetails::class] = $animalLivestockOverviewDetailsRepository;
        $this->repositories[ViewAnimalHistoricLocations::class] = $animalHistoricLocationsRepository;
        $this->repositories[ViewAnimalIsPublicDetails::class] = $animalIsPublicDetailsRepository;
        $this->repositories[ViewLitterDetails::class] = $viewLitterDetailsRepository;
        $this->repositories[ViewLocationDetails::class] = $viewLocationDetailsRepository;
        $this->repositories[ViewMinimalParentDetails::class] = $viewMinimalParentDetailsRepository;
        $this->repositories[ViewPedigreeRegisterAbbreviation::class] = $viewPedigreeRegisterAbbreviationRepository;
        $this->repositories[ViewPersonFullName::class] = $viewPersonFullNameRepository;
        $this->repositories[ViewBreedValueMaxGenerationDate::class] = $viewBreedValueMaxGenerationDateRepository;
    }


    /**
     * @param $clazz
     * @return SqlViewRepositoryInterface
     * @throws \Exception
     */
    public function get($clazz)
    {
        if (!key_exists($clazz, $this->repositories)) {
            throw new \Exception('View repository '.$clazz.' has not yet been registered', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->repositories[$clazz];
    }
}
