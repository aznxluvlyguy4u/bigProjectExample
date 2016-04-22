<?php

namespace AppBundle\Service;


use Doctrine\Common\Collections\ArrayCollection;

interface IRSerializerInterface
{
    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclarationDetail(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareAnimalFlag(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareArrival(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareBirth(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareDepart(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareEartagsTransfer(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareLoss(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareExport(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseDeclareImport(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseRetrieveEartags(ArrayCollection $contentArray);

    /**
     * @param ArrayCollection $contentArray
     * @return mixed
     */
    function parseRevokeDeclaration(ArrayCollection $contentArray);
}