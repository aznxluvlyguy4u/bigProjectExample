<?php


namespace AppBundle\Service;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Templating\EngineInterface;

class EmailService extends ControllerServiceBase
{
    private $swiftMailer;
    /** @var string */
    private $mailerSourceAddress;
    /** @var EngineInterface */
    private $templating;

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer, CacheService $cacheService, UserService $userService, \Swift_Mailer $swiftMailer, $mailerSourceAddress, EngineInterface $templating)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);

        $this->swiftMailer = $swiftMailer;
        $this->mailerSourceAddress = $mailerSourceAddress;
        $this->templating = $templating;
    }


    /**
     * @param $emailAddress
     */
    public function sendNewPasswordEmail($emailAddress)
    {
        //Confirmation message back to the sender
        $message = \Swift_Message::newInstance()
            ->setSubject(Constant::NEW_PASSWORD_MAIL_SUBJECT_HEADER)
            ->setFrom($this->mailerSourceAddress)
            ->setTo($emailAddress)
            ->setBody(
                $this->templating->render(
                // app/Resources/views/...
                    'User/change_password_email.html.twig'
                ),
                'text/html'
            )
            ->setSender($this->mailerSourceAddress);

        $this->swiftMailer->send($message);
    }


    /**
     * @param Person $person
     * @param $newPassword
     * @param bool $isAdmin
     * @param bool $isNewUser
     */
    public function emailNewPasswordToPerson($person, $newPassword, $isAdmin = false, $isNewUser = false)
    {
        if($isAdmin) {
            $subjectHeader = Constant::NEW_ADMIN_PASSWORD_MAIL_SUBJECT_HEADER;
        } else {
            $subjectHeader = Constant::NEW_PASSWORD_MAIL_SUBJECT_HEADER;
        }

        if($isNewUser) {
            $twig = 'User/new_user_email.html.twig';
        } else {
            $twig = 'User/reset_password_email.html.twig';
        }

        //Confirmation message back to the sender
        $message = \Swift_Message::newInstance()
            ->setSubject($subjectHeader)
            ->setFrom($this->mailerSourceAddress)
            ->setTo($person->getEmailAddress())
            ->setBcc($this->mailerSourceAddress)
            ->setBody(
                $this->templating->render(
                // app/Resources/views/...
                    $twig,
                    array('firstName' => $person->getFirstName(),
                        'lastName' => $person->getLastName(),
                        'userName' => $person->getUsername(),
                        'email' => $person->getEmailAddress(),
                        'password' => $newPassword)
                ),
                'text/html'
            )
            ->setSender($this->mailerSourceAddress)
        ;

        $this->swiftMailer->send($message);
    }
}