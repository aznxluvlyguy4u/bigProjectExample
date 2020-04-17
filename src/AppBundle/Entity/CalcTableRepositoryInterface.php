<?php


namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

interface CalcTableRepositoryInterface {
    function tableName(): string;
    function truncate(?LoggerInterface $logger = null);
}
