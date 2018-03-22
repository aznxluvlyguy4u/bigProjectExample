<?php


namespace AppBundle\Service\Migration;


use AppBundle\Util\SqlView;

class SqlViewInitializer extends DatabaseContentInitializerBase implements DatabaseContentInitializerInterface
{

    function initialize()
    {
        // Some views are dependent on others, so run at least twice
        for($i = 0; $i <= 1; $i++) {
            foreach (SqlView::getConstants() as $sqlViewName) {
                $this->getLogger()->notice('Processing '.$sqlViewName.' ...');
                SqlView::createViewIfNotExists($this->getConnection(), $sqlViewName);
            }
        }

        $this->getLogger()->notice('Done');
    }


    function update()
    {
        $upsertCount = 0;

        // Some views are dependent on others, so run at least twice
        for($i = 0; $i <= 1; $i++) {
            foreach (SqlView::getConstants() as $sqlViewName) {
                $upsertResult = SqlView::createOrUpdateView($this->getConnection(), $sqlViewName);
                if ($upsertResult) {
                    $upsertCount++;
                    $this->getLogger()->notice($sqlViewName . ' upserted');
                } else {
                    $this->getLogger()->notice($sqlViewName . ' error when upserting');
                }
            }
        }

        $this->getLogger()->notice('Upsert count: '.$upsertCount);
    }


}