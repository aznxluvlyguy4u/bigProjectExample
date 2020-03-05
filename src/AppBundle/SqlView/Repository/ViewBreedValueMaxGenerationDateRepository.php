<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewBreedValueMaxGenerationDate;
use AppBundle\Util\SqlView;

class ViewBreedValueMaxGenerationDateRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewBreedValueMaxGenerationDate::class);
        $this->setTableName(SqlView::VIEW_BREED_VALUE_MAX_GENERATION_DATE);
        $this->setPrimaryKeyName(ViewBreedValueMaxGenerationDate::getPrimaryKeyName());
    }


    public function getMaxGenerationDateAsDdMmYyyy()
    {
        $sql = "SELECT dd_mm_yyyy FROM view_breed_value_max_generation_date LIMIT 1";
        $ddMmYyyy = $this->getConnection()->query($sql)->fetchColumn(0);
        return empty($ddMmYyyy) ? '-' : strval($ddMmYyyy);
    }
}
