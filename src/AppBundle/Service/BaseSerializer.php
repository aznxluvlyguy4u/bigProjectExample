<?php

namespace AppBundle\Service;


use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\LazyCriteriaCollection;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        if ($basePath === self::ENTITY_NAMESPACE) {
            $messageClassPathNameSpace = strtr($basePath . $messageClassNameSpace,
                [self::ENTITY_NAMESPACE.self::ENTITY_NAMESPACE => self::ENTITY_NAMESPACE]);
        } else {
            $messageClassPathNameSpace = $messageClassNameSpace;
        }

        $messageObject = $this->jmsSerializer->deserialize($json, $messageClassPathNameSpace, Constant::jsonNamespace);

        return $messageObject;
    }


    /**
     * @param array $objects
     * @param array|string $type
     * @param bool $enableMaxDepthChecks
     * @return array
     */
    public function getArrayOfSerializedObjects($objects, $type = null, $enableMaxDepthChecks = true)
    {
        $serializedObjects = [];
        foreach ($objects as $object) {
            $serializedObjects[] = $this->serializeToJSON($object, $type, $enableMaxDepthChecks);
        }
        return $serializedObjects;
    }



    /**
     * @param array $jsonSerializedObjects
     * @param string $messageClassNameSpace
     * @param string $basePath
     * @return array
     */
    public function deserializeArrayOfObjects($jsonSerializedObjects, $messageClassNameSpace, $basePath = self::ENTITY_NAMESPACE)
    {
        $objects = [];
        foreach ($jsonSerializedObjects as $object) {
            $objects[] = $this->deserializeToObject($object, $messageClassNameSpace, $basePath);
        }
        return $objects;
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
     * @param $object
     * @param null $type
     * @param bool $enableMaxDepthChecks
     * @return array
     */
    public function normalizeResultTableToArray($object, $type = null, $enableMaxDepthChecks = true)
    {
        if (!$object) {
            return [];
        }
        $array = $this->normalizeToArray($object, $type, $enableMaxDepthChecks);

        $keysToReplace = [
            'fat_thickness1_accuracy' => 'fat_thickness1accuracy',
            'fat_thickness2_accuracy' => 'fat_thickness2accuracy',
            'fat_thickness3_accuracy' => 'fat_thickness3accuracy',
            'weight_at8_weeks' => 'weight_at8weeks',
            'weight_at8_weeks_accuracy' => 'weight_at8weeks_accuracy',
            'weight_at20_weeks' => 'weight_at20weeks',
            'weight_at20_weeks_accuracy' => 'weight_at20weeks_accuracy',
        ];

        foreach ($keysToReplace as $oldKey => $newKey)
        {
            if (key_exists($oldKey, $array)) {
                $array[$newKey] = $array[$oldKey];
            }
        }

        return $array;
    }


    /**
     * @param array $array
     * @param $clazz
     * @param boolean $isArrayOfObjects
     * @param DeserializationContext $context
     * @return mixed
     */
    public function denormalizeToObject(array $array, $clazz, $isArrayOfObjects = false, DeserializationContext $context = null)
    {
        if (!$isArrayOfObjects) {
            return $this->jmsSerializer->fromArray($array, $clazz, $context);
        }

        $result = [];
        foreach ($array as $object) {
            $result[] = $this->jmsSerializer->fromArray($object, $clazz, $context);
        }

        return $result;
    }


    /**
     * @param string $content
     * @param $clazz
     * @param boolean $isArrayOfObjects
     * @param DeserializationContext $context
     * @return mixed
     */
    public function getObjectsFromRequestContent($content, $clazz, $isArrayOfObjects = false, DeserializationContext $context = null)
    {
        $objects = json_decode($content, true);
        if ($objects === null) {
            throw new BadRequestHttpException();
        }
        return $this->denormalizeToObject($objects,$clazz, $isArrayOfObjects, $context);
    }

}
