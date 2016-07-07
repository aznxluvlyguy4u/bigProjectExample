<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/contacts")
 */
class ContactAPIController extends APIController implements ContactAPIControllerInterface {


  /**
   *
   * Create a Client
   *
   * @Route("")
   * @Method("POST")
   */
  public function postContactEmail(Request $request) {

    $content = $this->getContentAsArray($request);
    $user = $this->getAuthenticatedUser($request);
    $ubn = $this->getSelectedUbn($request);
    
    $lastName = $user->getLastName();
    $firstName = $user->getFirstName();
    $userName = $lastName . ", " . $firstName;

    //Content format
    $emailAddressUser = $content->get('email');
    $category = $content->get('category');
    $mood = $content->get('mood');
    $messageBody = $content->get('message');

    //Message to NSFO
    $emailSourceAddress = $this->getParameter('mailer_source_address');
    $emailSender = 'info@stormdelta.com';
//    $emailSender = $emailSourceAddress;

    $message = \Swift_Message::newInstance()
        ->setSubject(Constant::CONTACT_CONFIRMATION_MAIL_SUBJECT_HEADER)
        ->setFrom($emailSender)
        ->setTo($emailSourceAddress)
        ->setBody(
            $this->renderView(
            // app/Resources/views/...
                'User/contact_email.html.twig',
                array('userName' => $userName,
                      'firstName' => $firstName,
                      'lastName' => $lastName,
                      'ubn' => $ubn,
                      'emailAddressUser' => $emailAddressUser,
                      'body' => $messageBody,
                      'category' => $category,
                      'mood' => $mood)
            ),
            'text/html'
        )
        ->setSender($emailSender)
    ;

    $this->get('mailer')->send($message);

    //Confirmation message back to the sender
    $messageConfirmation = \Swift_Message::newInstance()
        ->setSubject(Constant::CONTACT_MAIL_SUBJECT_HEADER)
        ->setFrom($emailSender)
        ->setTo($emailAddressUser)
        ->setBody(
            $this->renderView(
            // app/Resources/views/...
                'User/contact_verification_email.html.twig',
                array('userName' => $userName,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'ubn' => $ubn,
                    'emailAddressUser' => $emailAddressUser,
                    'body' => $messageBody,
                    'category' => $category,
                    'mood' => $mood)
            ),
            'text/html'
        )
        ->setSender($emailSender)
    ;

    $this->get('mailer')->send($messageConfirmation);

    return new JsonResponse(array("Message sent!" => $messageBody), 200); //$this->redirectToRoute('/');

  }



}