<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\EditTypeEnum;
use AppBundle\Util\SqlUtil;

class EditTypeRepository extends BaseRepository {

    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function initializeRecords(): int
    {
        /** @var EditType[] $editTypes */
        $editTypes = $this->findAll();
        $insertCount = 0;

        foreach (EditTypeEnum::getConstants() as $name => $id) {

            $continue = false;
            foreach ($editTypes as $editType) {
                if ($editType->getId() === $id) {
                    // Don't overwrite any existing values
                    $continue = true;
                    break;
                }
            }

            if ($continue) {
                continue;
            }

            $sql = "INSERT INTO edit_type (id, name) VALUES ($id, '$name')";
            $insertCount += SqlUtil::updateWithCount($this->getConnection(), $sql);
        }

        return $insertCount;
    }


    /**
     * @param int $editTypeEnumValue
     * @return null|EditType
     */
    public function getEditType($editTypeEnumValue)
    {
        return $this->findOneBy(['id' => $editTypeEnumValue]);
    }

}
