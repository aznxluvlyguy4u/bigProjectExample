<?php

namespace AppBundle\Service;



use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthServiceBase extends ControllerServiceBase
{
    /** @var EmailService */
    protected $emailService;
    /** @var UserPasswordEncoderInterface */
    protected $encoder;
    /** @var TwigEngine */
    private $templatingService;

    public function __construct(BaseSerializer $baseSerializer,
                                CacheService $cacheService,
                                EmailService $emailService,
                                EntityManagerInterface $manager,
                                UserService $userService,
                                UserPasswordEncoderInterface $encoder,
                                TwigEngine $templatingService)
    {
        parent::__construct($baseSerializer, $cacheService, $manager, $userService);
        $this->emailService = $emailService;
        $this->encoder = $encoder;
        $this->templatingService = $templatingService;
    }


    /**
     * @return TwigEngine
     */
    public function getTemplatingService()
    {
        return $this->templatingService;
    }
}