<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseRepository;
use AppBundle\Entity\DeclareBaseResponse;
use AppBundle\Entity\DeclareBaseResponseRepository;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareNsfoBaseRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ControllerServiceBase
 */
class ControllerServiceBase
{
    /** @var EntityManagerInterface|ObjectManager */
    protected $em;
    /** @var IRSerializer */
    protected $serializer;
    /** @var CacheService */
    protected $cacheService;
    /** @var UserService */
    protected $userService;

    /** @var Connection */
    protected $conn;

    /** @var AnimalRepository */
    protected $animalRepository;
    /** @var DeclareBaseRepository */
    protected $declareBaseRepository;
    /** @var DeclareBaseResponseRepository */
    protected $declareBaseResponseRepository;
    /** @var DeclareNsfoBaseRepository */
    protected $declareNsfoBaseRepository;
    /** @var LocationRepository */
    protected $locationRepository;
    /** @var TagRepository */
    protected $tagRepository;

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                CacheService $cacheService, UserService $userService)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->cacheService = $cacheService;
        $this->userService = $userService;

        $this->conn = $this->em->getConnection();

        $this->animalRepository = $this->em->getRepository(Animal::class);
        $this->declareBaseRepository = $this->em->getRepository(DeclareBase::class);
        $this->declareBaseResponseRepository = $this->em->getRepository(DeclareBaseResponse::class);
        $this->declareNsfoBaseRepository = $this->em->getRepository(DeclareNsfoBase::class);
        $this->locationRepository = $this->em->getRepository(Location::class);
        $this->tagRepository = $this->em->getRepository(Tag::class);
    }
}