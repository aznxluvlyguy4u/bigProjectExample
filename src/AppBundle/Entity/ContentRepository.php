<?php

namespace AppBundle\Entity;

/**
 * Class ContentRepository
 * @package AppBundle\Entity
 */
class ContentRepository extends BaseRepository {
    
    public function getCMS()
    {
        $repository = $this->getManager()->getRepository(Content::class);
        $content = $repository->find(1);

        if($content == null) {
            $content = new Content();
            $this->getManager()->persist($content);
            $this->getManager()->flush();
        }
        
        return $content;
    }


    /**
     * @return string
     */
    public function getDashBoardIntroductionText()
    {
        $sql = "SELECT dash_board_introduction_text FROM content WHERE id = 1";
        $result = $this->getManager()->getConnection()->query($sql)->fetch();
        if(array_key_exists('dash_board_introduction_text', $result)) {
            return $result['dash_board_introduction_text'];
        } else {
            return null;
        }
    }
    
}