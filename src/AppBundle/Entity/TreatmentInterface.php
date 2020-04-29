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
    public function getCreateDate();
    public function setCreateDate($logDate);
    public function getStartDate();
    public function setStartDate($startDate);
    public function getEndDate();
    public function setEndDate($endDate);
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