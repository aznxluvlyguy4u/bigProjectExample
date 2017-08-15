<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Util\ActionLogWriter;
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

    $om = $this->getDoctrine()->getManager();

    $content = $this->getContentAsArray($request);
    $user = $this->getAccountOwner($request);
    $ubn = $this->getSelectedUbn($request);
    $loggedInUser = $this->getUser();

    if($ubn == null) {
        $ubn = 'geen';
    }
    
    $lastName = $user->getLastName();
    $firstName = $user->getFirstName();
    $userName = $lastName . ", " . $firstName;

    //Content format
    $emailAddressUser = $content->get('email');
    $category = $content->get('category');
    $mood = $content->get('mood');
    $messageBody = $content->get('message');

    $contactMailSubjectHeader = 'NSFO Contactformulier '.$category.' | UBN '.$ubn.' ('.$lastName.') | '.$mood;
      
    //Message to NSFO
    $emailSourceAddress = $this->getParameter('mailer_source_address');
      
    $message = \Swift_Message::newInstance()
        ->setSubject($contactMailSubjectHeader)
        ->setFrom($emailSourceAddress) // EMAIL IS ONLY SENT IF THIS IS THE EMAIL ADDRESS!
        ->setTo($emailSourceAddress) //Send the original to kantoor@nsfo.nl
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
        ->setSender($emailSourceAddress)
    ;

    $this->get('mailer')->send($message);

    //Confirmation message back to the sender
    $messageConfirmation = \Swift_Message::newInstance()
        ->setSubject(Constant::CONTACT_CONFIRMATION_MAIL_SUBJECT_HEADER)
        ->setFrom($emailSourceAddress) // EMAIL IS ONLY SENT IF THIS IS THE EMAIL ADDRESS!
        ->setTo($emailAddressUser) //Send the confirmation back to the original sender
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
        ->setSender($emailSourceAddress)
    ;

    $this->get('mailer')->send($messageConfirmation);

    $log = ActionLogWriter::contactEmail($om, $user, $loggedInUser, $contactMailSubjectHeader);

    return new JsonResponse(array("Message sent!" => $messageBody), 200); //$this->redirectToRoute('/');

  }



}