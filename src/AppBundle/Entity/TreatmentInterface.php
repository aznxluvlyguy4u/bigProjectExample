<?php

namespace AppBundle\Entity;

/**
 * Interface TreatmentInterface
 * @package AppBundle\Entity
 */
interface TreatmentInterface
{
    public function getId();
    public function setId($id);
    public function getLogDate();
    public function setLogDate($logDate);
    public function getTreatmentStartDate();
    public function setTreatmentStartDate($treatmentStartDate);
    public function getTreatmentEndDate();
    public function setTreatmentEndDate($treatmentEndDate);
    public function getOwner();
    public function setOwner($owner);
    public function getCreationBy();
    public function setCreationBy($creationBy);
    public function getEditedBy();
    public function setEditedBy($editedBy);
    public function getDeletedBy();
    public function setDeletedBy($deletedBy);
    public function getDescription();
    public function setDescription($description);
    public function getDosage();
    public function setDosage($dosage);
    public function isActive();
    public function setIsActive($isActive);
}