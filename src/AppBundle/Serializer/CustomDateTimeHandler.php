<?php


namespace AppBundle\Serializer;


use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;

class CustomDateTimeHandler extends DateHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'DateTime',
                'method' => 'deserializeDateTimeFromJson',
            ),
        );
    }

    /**
     * This custom handler is not properly registered by the jms serializer yet!
     *
     * @param JsonDeserializationVisitor $visitor
     * @param $data
     * @param array $type
     * @return bool|\DateTime|\DateTimeImmutable|null|static
     */
    public function deserializeDateTimeFromJson(JsonDeserializationVisitor $visitor, $data, array $type)
    {
        if ($data === '') {
            return null;
        }

        return parent::deserializeDateTimeFromJson($visitor, $data, $type);
    }
}