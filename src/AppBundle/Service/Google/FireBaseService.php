<?php

namespace AppBundle\Service\Google;

use AppBundle\Constant\Environment;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\MessageToRegistrationToken;
use Kreait\Firebase\Messaging\MessageToTopic;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\ServiceAccount;

class FireBaseService
{
    private $fireBase;
    private $messaging;

    public function __construct($projectId, $clientId, $clientEmail, $privateKey)
    {
        $firebaseCredentials = [
            'project_id' => $projectId,
            'client_id' => $clientId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];

        $serviceAccount = ServiceAccount::fromArray($firebaseCredentials);

        $this->fireBase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->create();

        $this->messaging = $this->fireBase->getMessaging();
    }

    public function sendMessageToDevice($deviceToken, $title, $body, $data = null)
    {
        try {
            $notification = Notification::create($title, $body);

            $message = MessageToRegistrationToken::create($deviceToken)
                ->withNotification($notification);

            if ($data != null)
                $message = $message->withData($data);

            $this->messaging->send($message);
        } catch(MessagingException $e){

        }
    }

    public function sendMessageToTopic($topic, $title, $body, $data = null)
    {
        $notification = Notification::create($title, $body);

        $message = MessageToTopic::create($topic)
            ->withNotification($notification);

        if($data != null)
            $message = $message->withData($data);

        $this->messaging->send($message);
    }
}