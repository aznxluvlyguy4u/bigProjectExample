<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\TokenType;

class TokenRepository extends BaseRepository {

    /**
     * @param Person $person
     * @return Token|null
     */
    public function findByClientIdPrioritizedByGhostTokenType(Person $person): ?Token
    {
        $token = $this->findOneBy(['owner' => $person->getId(), 'type' => TokenType::GHOST]);
        if (!$token) {
            $token = $this->findOneBy(['owner' => $person->getId(), 'type' => TokenType::ACCESS]);
        }
        return $token;
    }
}
