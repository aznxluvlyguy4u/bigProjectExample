<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewPersonFullName;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewPersonFullNameRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewPersonFullName::class);
        $this->setTableName(SqlView::VIEW_PERSON_FULL_NAME);
        $this->setPrimaryKeyName(ViewPersonFullName::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $personIds
     * @return ArrayCollection|ViewPersonFullName[]
     * @throws \Exception
     */
    public function findByPersonIds($personIds = [])
    {
        return $this->findByPrimaryIds($personIds);
    }


    /**
     * @param int $personId
     * @return ViewPersonFullName
     * @throws \Exception
     */
    public function findOneByPersonId($personId)
    {
        return $this->findOneByPrimaryId($personId);
    }
}