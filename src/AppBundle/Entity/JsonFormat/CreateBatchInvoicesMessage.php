<?php


namespace AppBundle\JsonFormat;

use JMS\Serializer\Annotation as JMS;

class CreateBatchInvoicesMessage
{
    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    private $controlDate;
}