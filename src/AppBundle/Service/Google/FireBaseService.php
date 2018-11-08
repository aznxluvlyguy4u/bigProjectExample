<?php

namespace AppBundle\Service\Google;

use AppBundle\Entity\Message as NsfoNotificationMessage;
use AppBundle\Entity\Person;
use AppBundle\Util\ExceptionUtil;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MessageData;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\ServiceAccount;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FireBaseService
{
    private $fireBase;
    private $messaging;
    /** @var LoggerInterface */
    private $logger;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct($projectId, $clientId, $clientEmail, $privateKey,
                                LoggerInterface $logger,
                                TranslatorInterface $translator
    )
    {
        $this->logger = $logger;
        $this->translator = $translator;

        $fireBaseCredentials = [
            'project_id' => $projectId,
            'client_id' => $clientId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];

        $serviceAccount = ServiceAccount::fromArray($fireBaseCredentials);

        $this->fireBase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->create();

        $this->messaging = $this->fireBase->getMessaging();
    }


    /**
     * @param Person $person
     * @param NsfoNotificationMessage $message
     */
    public function sendNsfoMessageToUser(Person $person, NsfoNotificationMessage $message)
    {
        foreach($person->getMobileDevices() as $mobileDevice) {
            $title = $this->translator->trans($message->getNotificationMessageTranslationKey());
            $this->sendMessageToDevice(
                $mobileDevice->getRegistrationToken(),
                $title,
                $message->getData(),
                $message->getDataForFireBase()
            );
        }
    }


    /**
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param MessageData|array|null $data
     */
    public function sendMessageToDevice($deviceToken, $title, $body, $data = null)
    {
        $this->sendMessageBase(MessageTarget::TOKEN, $deviceToken, $title, $body, $data);
    }

    /**
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param MessageData|array|null $data
     */
    public function sendMessageToTopic($topic, $title, $body, $data = null)
    {
        $this->sendMessageBase(MessageTarget::TOPIC, $topic, $title, $body, $data);
    }


    /**
     * @param string $messageTypeKey
     * @param string $messageTypeValue
     * @param string $title
     * @param string $body
     * @param MessageData|array|null $data
     */
    private function sendMessageBase($messageTypeKey, $messageTypeValue, $title, $body, $data = null)
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget($messageTypeKey, $messageTypeValue)
                ->withNotification($notification);

            if (!empty($data)) {
                $message = $message->withData($data);
            }

            $this->messaging->send($message);

        } catch(MessagingException $e) {
            ExceptionUtil::logException($this->logger, $e);
        }
    }
}