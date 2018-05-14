<?php


namespace AppBundle\SqlView;


use AppBundle\SqlView\Repository\SqlViewRepositoryInterface;

interface SqlViewManagerInterface
{
    /**
     * @param string $clazz
     * @return SqlViewRepositoryInterface
     */
    function get($clazz);
}