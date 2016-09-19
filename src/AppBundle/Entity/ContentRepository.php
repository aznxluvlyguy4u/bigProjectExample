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
    
}