<?php

namespace AppBundle\Component\HttpFoundation;

use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Response;

class JsonResponse extends Response
{
    protected $data;
    protected $json = false;

    public function __construct($data = null, $status, $headers = array())
    {
        parent::__construct(null, $status, $headers);

        // IF JSON OR NOT
        $this->json ? $this->setJson($data) : $this->setData($data);
    }

    public function isJson($is_json)
    {
        $this->json = $is_json;
    }

    public function setData($data)
    {
        $serializer = SerializerBuilder::create()->build();
        $data = $serializer->serialize($data, 'json');

        $this->setJson($data);

        return $this;
    }

    public function setJson($json)
    {
        $this->data = $json;
        $this->update();

        return $this;
    }

    protected function update()
    {
        $this->headers->set('Content-Type', 'application/json');
//        $this->headers->set("Access-Control-Allow-Origin", '*');
        $this->setContent($this->data);
    }
}
