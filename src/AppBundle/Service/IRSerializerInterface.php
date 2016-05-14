<?php

namespace AppBundle\Service;


use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RevokeDeclaration;
use Doctrine\Common\Collections\ArrayCollection;

interface IRSerializerInterface
{
    /**
     * @param ArrayCollection $contentArray
     * @return DeclarationDetail
     */
    function parseDeclarationDetail(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareAnimalFlag
     */
    function parseDeclareAnimalFlag(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareArrival
     */
    function parseDeclareArrival(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareBirth
     */
    function parseDeclareBirth(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareDepart
     */
    function parseDeclareDepart(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareTagsTransfer
     */
    function parseDeclareTagsTransfer(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareLoss
     */
    function parseDeclareLoss(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareExport
     */
    function parseDeclareExport(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return DeclareImport
     */
    function parseDeclareImport(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return RetrieveTags
     */
    function parseRetrieveTags(ArrayCollection $contentArray, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @return RevokeDeclaration
     */
    function parseRevokeDeclaration(ArrayCollection $contentArray, $isEditMessage);
}