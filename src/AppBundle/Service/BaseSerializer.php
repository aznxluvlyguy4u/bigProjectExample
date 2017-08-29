<?php

namespace AppBundle\Service;


use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\LazyCriteriaCollection;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;

class BaseSerializer
{
    const ENTITY_NAMESPACE = "AppBundle\\Entity\\";

    /** @var Serializer */
    private $jmsSerializer;

    public function __construct(Serializer $jmsSerializer)
    {
        $this->jmsSerializer = $jmsSerializer;
    }


    /**
     * @param $object
     * @param array|string $type
     * @param bool $enableMaxDepthChecks
     * @return mixed
     */
    public function getDecodedJson($object, $type = null, $enableMaxDepthChecks = true)
    {
        if($object instanceof ArrayCollection || is_array($object) || $object instanceof LazyCriteriaCollection) {
            $results = [];
            foreach ($object as $item) {
                $results[] = $this->getDecodedJsonSingleObject($item, $type, $enableMaxDepthChecks);
            }
            return $results;
        }

        return $this->getDecodedJsonSingleObject($object, $type, $enableMaxDepthChecks);
    }


    /**
     * @param $object
     * @param array $type
     * @param boolean $enableMaxDepthChecks
     * @return mixed|array
     */
    private function getDecodedJsonSingleObject($object, $type = null, $enableMaxDepthChecks = true)
    {
        $jsonMessage = $this->serializeToJSON($object, $type, $enableMaxDepthChecks);
        return json_decode($jsonMessage, true);
    }


    /**
     * @param $object
     * @param $type array|string
     * @param $enableMaxDepthChecks boolean
     * @return mixed|string
     */
    public function serializeToJSON($object, $type = null, $enableMaxDepthChecks = true)
    {
        if($type == '' || $type == null) {
            if($enableMaxDepthChecks) {
                $serializationContext = SerializationContext::create()->enableMaxDepthChecks();
            } else {
                $serializationContext = null;
            }

        } else {
            $type = is_string($type) ? [$type] : $type;
            if($enableMaxDepthChecks) {
                $serializationContext = SerializationContext::create()->setGroups($type)->enableMaxDepthChecks();
            } else {
                $serializationContext = SerializationContext::create()->setGroups($type);
            }
        }

        return $this->jmsSerializer->serialize($object, Constant::jsonNamespace, $serializationContext);
    }

    /**
     * @param $json
     * @param $messageClassNameSpace
     * @param $basePath
     * @return array|mixed|object
     */
    public function deserializeToObject($json, $messageClassNameSpace, $basePath = self::ENTITY_NAMESPACE)
    {
        $messageClassPathNameSpace = strtr($basePath . $messageClassNameSpace,
            [self::ENTITY_NAMESPACE.self::ENTITY_NAMESPACE => self::ENTITY_NAMESPACE]);

        $messageObject = $this->jmsSerializer->deserialize($json, $messageClassPathNameSpace, Constant::jsonNamespace);

        return $messageObject;
    }

    /**
     * @param $object
     * @param $type array|string The JMS Groups
     * @param $enableMaxDepthChecks boolean
     * @return array
     */
    public function normalizeToArray($object, $type = null, $enableMaxDepthChecks = true)
    {
        $json = $this->serializeToJSON($object, $type, $enableMaxDepthChecks);
        $array = json_decode($json, true);

        return $array;
    }


    /**
     * @param array $array
     * @param $clazz
     * @param DeserializationContext $context
     * @return mixed
     */
    public function denormalizeToObject(array $array, $clazz, DeserializationContext $context = null)
    {
        return $this->jmsSerializer->fromArray($array, $clazz, $context);
    }
}