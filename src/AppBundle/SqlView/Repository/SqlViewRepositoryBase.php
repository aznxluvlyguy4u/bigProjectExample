<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\Service\BaseSerializer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class SqlViewRepositoryBase
{
    /** @var string */
    private $clazz;
    /** @var string */
    private $tableName;
    /** @var string */
    private $primaryKeyName;

    /** @var BaseSerializer */
    private $serializer;
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(BaseSerializer $serializer, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * @return BaseSerializer
     */
    protected function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getManager()
    {
        return $this->em;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection()
    {
        return $this->em->getConnection();
    }

    /**
     * @return string
     */
    protected function getClazz()
    {
        return $this->clazz;
    }

    /**
     * @param string $clazz
     */
    protected function setClazz($clazz)
    {
        $this->clazz = $clazz;
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    protected function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    protected function getPrimaryKeyName()
    {
        return $this->primaryKeyName;
    }

    /**
     * @param string $primaryKeyName
     */
    protected function setPrimaryKeyName($primaryKeyName)
    {
        $this->primaryKeyName = $primaryKeyName;
    }


    /**
     * @param array $primaryKeys
     * @return ArrayCollection
     * @throws \Exception
     */
    protected function findByPrimaryIds($primaryKeys = [])
    {
        $sqlResults = $this->getResults($primaryKeys);
        $objects = $this->denormalizeToObjects($sqlResults);
        return new ArrayCollection($objects);
    }


    /**
     * @param int $primaryId
     * @return mixed|null
     * @throws \Exception
     */
    protected function findOneByPrimaryId($primaryId)
    {
        $results = $this->findByPrimaryIds([$primaryId]);
        $result = $results->first();
        return $result ? $result : null;
    }


    /**
     * @param array $sqlResults
     * @return mixed
     * @throws \Exception
     */
    protected function denormalizeToObjects($sqlResults = [])
    {
        return $this->getSerializer()->denormalizeToObject(
            $sqlResults,
            $this->getClazz(),
            true
        );
    }


    /**
     * @param array $primaryKeys
     * @return array
     * @throws \Exception
     */
    protected function getResults($primaryKeys = [])
    {
        if (count($primaryKeys) === 0) {
            return [];
        }

        if (!ctype_digit(implode($primaryKeys))) {
            throw new \Exception(
                $this->getPrimaryKeyName().'s must be integers. Given input: '.implode($primaryKeys),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $sql = "SELECT * FROM ".$this->getTableName()." WHERE ".$this->getPrimaryKeyName()." IN (".implode(',',$primaryKeys).")";
        return $this->getConnection()->query($sql)->fetchAll();
    }
}