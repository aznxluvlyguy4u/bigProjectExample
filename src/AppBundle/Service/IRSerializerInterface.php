<?php

namespace AppBundle\Service;


use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareEartagsTransfer;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\RetrieveEartags;
use AppBundle\Entity\RevokeDeclaration;
use Doctrine\Common\Collections\ArrayCollection;

interface IRSerializerInterface
{
    /**
     * @param ArrayCollection $contentArray
     * @return DeclarationDetail
     */
    function parseDeclarationDetail(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareAnimalFlag
     */
    function parseDeclareAnimalFlag(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareArrival
     */
    function parseDeclareArrival(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareBirth
     */
    function parseDeclareBirth(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareDepart
     */
    function parseDeclareDepart(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareEartagsTransfer
     */
    function parseDeclareEartagsTransfer(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareLoss
     */
    function parseDeclareLoss(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareExport
     */
    function parseDeclareExport(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareImport
     */
    function parseDeclareImport(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return RetrieveEartags
     */
    function parseRetrieveEartags(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return RevokeDeclaration
     */
    function parseRevokeDeclaration(ArrayCollection $contentArray);
}