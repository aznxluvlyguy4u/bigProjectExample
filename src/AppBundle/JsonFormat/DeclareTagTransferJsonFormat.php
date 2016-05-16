<?php

namespace AppBundle\JsonFormat;


use AppBundle\Entity\Tag;

class DeclareTagTransferJsonFormat
{
    /**
     * @var string
     */
    private $relationNumberAcceptant;

    /**
     * @var array
     */
    private $tags;

    /**
     * DeclareTagTransferJsonFormat constructor.
     */
    public function __construct()
    {
        $this->tags = array();
    }

    /**
     * @return string
     */
    public function getRelationNumberAcceptant()
    {
        return $this->relationNumberAcceptant;
    }

    /**
     * @param string $relationNumberAcceptant
     */
    public function setRelationNumberAcceptant($relationNumberAcceptant)
    {
        $this->relationNumberAcceptant = $relationNumberAcceptant;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        $tagJsonFormat = new TagJsonFormat();
        $tagJsonFormat->setTag($tag);
        $this->tags[] = $tagJsonFormat;
    }

    /**
     * @param array $array
     */
    public function addTags($tags)
    {
        foreach($tags as $tag){
            $this->addTag($tag);
        }
    }


}