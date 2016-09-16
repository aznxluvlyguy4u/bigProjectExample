<?php

namespace AppBundle\Entity;

/**
 * Class ContentRepository
 * @package AppBundle\Entity
 */
class ContentRepository extends BaseRepository {
    
    public function getCMS()
    {
        $repository = $this->getEntityManager()->getRepository(Content::class);
        $content = $repository->find(1);

        if($content == null) {
            $content = new Content();
            $this->getEntityManager()->persist($content);
            $this->getEntityManager()->flush();
        }
        
        return $content;
    }
    
}