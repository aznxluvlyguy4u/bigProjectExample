<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\ContactAPIControllerInterface;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

class ContactService extends AuthServiceBase implements ContactAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postContactEmail(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $user = $this->getAccountOwner($request);
        $ubn = $this->getSelectedUbn($request);
        $loggedInUser = $this->getUser();

        if($ubn === null) { $ubn = 'geen'; }

        $lastName = $user->getLastName();
        $firstName = $user->getFirstName();
        $userName = $lastName . ", " . $firstName;

        //Content format
        $emailAddressUser = $content->get('email');
        $category = $content->get('category');
        $mood = $content->get('mood');
        $messageBody = $content->get('message');

        $contactMailSubjectHeader = 'NSFO Contactformulier '.$category.' | UBN '.$ubn.' ('.$lastName.') | '.$mood;

        $emailData = array('userName' => $userName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'ubn' => $ubn,
            'emailAddressUser' => $emailAddressUser,
            'body' => $messageBody,
            'category' => $category,
            'mood' => $mood);

        //Message to NSFO

        $isSent = $this->emailService->sendContactEmail($contactMailSubjectHeader, $emailData);

        if ($isSent) {
            //Confirmation message back to the sender
            $this->emailService->sendContactVerificationEmail($emailData);
            $description = $contactMailSubjectHeader;
        } else {
            $description = 'Failed sending contactEmail: '. implode(', ', $emailData);
        }

        $log = ActionLogWriter::contactEmail($this->getManager(), $user, $loggedInUser, $description, $isSent);

        return ResultUtil::successResult($messageBody); //$this->redirectToRoute('/');

    }
}