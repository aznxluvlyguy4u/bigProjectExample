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
        $this->dropViews();

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


    private function dropViews()
    {
        $dropCount = 0;

        foreach (SqlView::getConstants() as $sqlViewName) {
            $dropResult = SqlView::dropView($this->getConnection(), $sqlViewName);
            if ($dropResult) {
                $dropCount++;
                $this->getLogger()->notice($sqlViewName . ' dropped');
            } else {
                $this->getLogger()->notice($sqlViewName . ' error when dropping');
            }
        }

        $this->getLogger()->notice('Drop count: '.$dropCount);
    }
}