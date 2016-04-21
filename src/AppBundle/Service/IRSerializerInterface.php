<?php

namespace AppBundle\Service;


use Doctrine\Common\Collections\ArrayCollection;

interface IRSerializerInterface
{
    function parseDeclarationDetail(ArrayCollection $contentArray);
    function parseDeclareAnimalFlag(ArrayCollection $contentArray);
    function parseDeclareArrival(ArrayCollection $contentArray);
    function parseDeclareBirth(ArrayCollection $contentArray);
    function parseDeclareDepart(ArrayCollection $contentArray);
    function parseDeclareEartagsTransfer(ArrayCollection $contentArray);
    function parseDeclareLoss(ArrayCollection $contentArray);
    function parseDeclareExport(ArrayCollection $contentArray);
    function parseDeclareImport(ArrayCollection $contentArray);
    function parseRetrieveEartags(ArrayCollection $contentArray);
    function parseRevokeDeclaration(ArrayCollection $contentArray);
}