<?php


namespace AppBundle\Service;


use AppBundle\Constant\Constant;
use AppBundle\Constant\Environment;
use AppBundle\Entity\Person;
use Symfony\Component\Templating\EngineInterface;

class EmailService
{
    /** @var string */
    private $environment;
    /** @var \Swift_Mailer */
    private $swiftMailer;
    /** @var string */
    private $mailerSourceAddress;
    /** @var EngineInterface */
    private $templating;

    public function __construct(\Swift_Mailer $swiftMailer, $mailerSourceAddress, EngineInterface $templating, $environment)
    {
        $this->environment = $environment;
        $this->swiftMailer = $swiftMailer;
        $this->mailerSourceAddress = $mailerSourceAddress;
        $this->templating = $templating;
    }


    /**
     * @param $emailAddress
     * @return boolean false if email not sent to anyone
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

        return $this->swiftMailer->send($message) > 0;
    }


    /**
     * @param Person $person
     * @param $newPassword
     * @param bool $isAdmin
     * @param bool $isNewUser
     * @return boolean false if email not sent to anyone
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

        //Only send BCC in prod env
        if ($this->environment === Environment::PROD) {
            $message->setBcc($this->mailerSourceAddress);
        }

        return $this->swiftMailer->send($message) > 0;
    }


    /**
     * @param string $contactMailSubjectHeader
     * @param array $emailData
     * @return bool
     */
    public function sendContactEmail($contactMailSubjectHeader, $emailData)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($contactMailSubjectHeader)
            ->setFrom($this->mailerSourceAddress) // EMAIL IS ONLY SENT IF THIS IS THE EMAIL ADDRESS!
            ->setTo($this->mailerSourceAddress) //Send the original to kantoor@nsfo.nl
            ->setBody(
                $this->templating->render(
                // app/Resources/views/...
                    'User/contact_email.html.twig',
                    $emailData
                ),
                'text/html'
            )
            ->setSender($this->mailerSourceAddress)
        ;

        return $this->swiftMailer->send($message) > 0;
    }


    /**
     * @param array $emailData
     * @return bool
     */
    public function sendContactVerificationEmail($emailData)
    {
        $emailAddressUser = $emailData['emailAddressUser'];

        $messageConfirmation = \Swift_Message::newInstance()
            ->setSubject(Constant::CONTACT_CONFIRMATION_MAIL_SUBJECT_HEADER)
            ->setFrom($this->mailerSourceAddress) // EMAIL IS ONLY SENT IF THIS IS THE EMAIL ADDRESS!
            ->setTo($emailAddressUser) //Send the confirmation back to the original sender
            ->setBody(
                $this->templating->render(
                // app/Resources/views/...
                    'User/contact_verification_email.html.twig',
                    $emailData
                ),
                'text/html'
            )
            ->setSender($this->mailerSourceAddress)
        ;

        return $this->swiftMailer->send($messageConfirmation) > 0;
    }


}