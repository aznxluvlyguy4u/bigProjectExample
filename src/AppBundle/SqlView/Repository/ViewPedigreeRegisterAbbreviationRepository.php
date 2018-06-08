<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewPedigreeRegisterAbbreviation;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewPedigreeRegisterAbbreviationRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewPedigreeRegisterAbbreviation::class);
        $this->setTableName(SqlView::VIEW_PEDIGREE_REGISTER_ABBREVIATION);
        $this->setPrimaryKeyName(ViewPedigreeRegisterAbbreviation::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $pedigreeRegisterIds
     * @return ArrayCollection|ViewPedigreeRegisterAbbreviation[]
     * @throws \Exception
     */
    public function findByPedigreeRegisterIds($pedigreeRegisterIds = [])
    {
        return $this->findByPrimaryIds($pedigreeRegisterIds);
    }


    /**
     * @param int $pedigreeRegisterId
     * @return ViewPedigreeRegisterAbbreviation
     * @throws \Exception
     */
    public function findOneByPedigreeRegisterId($pedigreeRegisterId)
    {
        return $this->findOneByPrimaryId($pedigreeRegisterId);
    }
}