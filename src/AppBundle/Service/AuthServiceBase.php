<?php

namespace AppBundle\Service;



use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthServiceBase extends ControllerServiceBase
{
    /** @var EmailService */
    protected $emailService;
    /** @var UserPasswordEncoderInterface */
    protected $encoder;

    public function __construct(BaseSerializer $baseSerializer,
                                CacheService $cacheService,
                                EmailService $emailService,
                                EntityManagerInterface $manager,
                                UserService $userService,
                                UserPasswordEncoderInterface $encoder)
    {
        parent::__construct($baseSerializer, $cacheService, $manager, $userService);
        $this->emailService = $emailService;
        $this->encoder = $encoder;
    }
}