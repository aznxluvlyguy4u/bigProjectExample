<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Variable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Class AnimalAnnotationRepository
 * @package AppBundle\Entity
 */
class AnimalAnnotationRepository extends BaseRepository
{

    /**
     * @param  int  $animalId
     * @return array
     */
    public function findByAnimalId(int $animalId): array {
        $qb = $this->getManager()->createQueryBuilder();

        $qb = $this->baseQuery($qb, $animalId);

        $query = $this->setFetchModes($qb);
        return $query->getResult();
    }


    /**
     * @param  int  $animalId
     * @param  int  $companyId
     * @return AnimalAnnotation|null
     */
    public function findByAnimalIdAndCompanyId(int $animalId, int $companyId): ?AnimalAnnotation
    {
        $qb = $this->getManager()->createQueryBuilder();

        $qb = $this->baseQuery($qb, $animalId)
            ->andWhere($qb->expr()->eq('company.id', $companyId))
        ;

        $query = $this->setFetchModes($qb);
        return $query->getOneOrNullResult();
    }

    /**
     * @param  QueryBuilder  $qb
     * @return QueryBuilder
     */
    private function baseQuery(QueryBuilder $qb, int $animalId): QueryBuilder {
        return $qb
            ->select('annotation')
            ->from(AnimalAnnotation::class, 'annotation')
            ->innerJoin('annotation.animal', 'animal', Join::WITH,
                $qb->expr()->eq('annotation.animal', 'animal.id')
            )
            ->innerJoin('annotation.actionBy', 'actionBy', Join::WITH,
                $qb->expr()->eq('annotation.actionBy', 'actionBy.id')
            )
            ->innerJoin('annotation.company', 'company', Join::WITH,
                $qb->expr()->eq('annotation.company', 'company.id')
            )
            ->innerJoin('annotation.location', 'location', Join::WITH,
                $qb->expr()->eq('annotation.location', 'location.id')
            )
            ->orderBy('annotation.updatedAt', Criteria::DESC)
            ->where($qb->expr()->eq('animal.id', $animalId))
        ;
    }


    private function setFetchModes(QueryBuilder $qb): Query {
        $query = $qb->getQuery();
        $query->setFetchMode(Animal::class, Variable::LOCATION, ClassMetadata::FETCH_EAGER);
        $query->setFetchMode(Person::class, Variable::PERSON, ClassMetadata::FETCH_EAGER);
        $query->setFetchMode(Company::class, Variable::COMPANY, ClassMetadata::FETCH_EAGER);
        $query->setFetchMode(Location::class, Variable::LOCATION, ClassMetadata::FETCH_EAGER);
        return $query;
    }
}
