<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class LocationHealthInspectionResultRepository
 * @package AppBundle\Entity
 */
class LocationHealthInspectionResultRepository extends BaseRepository
{
    public function createInspectionResults($illness, $location, $inspection, $results) {

        /** @var LocationHealthInspection $inspection */
        $inspection->setResults(new ArrayCollection());
        $this->getManager()->persist($inspection);

        foreach($results as $contentResult) {
            $ulnCountryCode = $contentResult['uln_country_code'];
            $ulnNumber = $contentResult['uln_number'];

            $repository = $this->getManager()->getRepository(Animal::class);
            $animal = $repository->findOneBy(['ulnCountryCode' => $ulnCountryCode, 'ulnNumber' => $ulnNumber, 'location' => $location]);

            if($animal == null) {
                return false;
            }

            $result = new LocationHealthInspectionResult();
            $result->setInspection($inspection);
            $result->setAnimal($animal);

            if($illness == 'SCRAPIE') {
                $result->setCustomerSampleId($contentResult['customer_sample_id']);
                $result->setMgxSampleId($contentResult['mgx_sample_id']);
                $result->setGenotype($contentResult['genotype']);
                $result->setGenotypeWithCondon($contentResult['genotype_with_condon']);
                $result->setGenotypeClass($contentResult['genotype_class']);
                $result->setReceptionDate(new \DateTime($contentResult['reception_date']));
                $result->setResultDate(new \DateTime($contentResult['result_date']));
            }

            if($illness == 'MAEDI VISNA') {
                $result->setCustomerSampleId($contentResult['customer_sample_id']);
                $result->setVetName($contentResult['vet_name']);
                $result->setSubRef($contentResult['subref']);
                $result->setMvnp($contentResult['mvnp']);
                $result->setMvCAEPool($contentResult['mv_cae_pool']);
                $result->setResultDate(new \DateTime($contentResult['result_date']));
            }

            // Save to Database
            $this->getManager()->persist($result);
        }
        return true;
    }
}