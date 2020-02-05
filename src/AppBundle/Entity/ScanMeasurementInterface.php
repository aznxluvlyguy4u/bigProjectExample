<?php


namespace AppBundle\Entity;


interface ScanMeasurementInterface
{
    public function getScanMeasurementSet(): ?ScanMeasurementSet;
    public function setScanMeasurementSet(?ScanMeasurementSet $scanMeasurementSet);
}
