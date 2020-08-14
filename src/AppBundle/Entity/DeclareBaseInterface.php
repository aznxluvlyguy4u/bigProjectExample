<?php


namespace AppBundle\Entity;


interface DeclareBaseInterface
{
    /**
     * @return integer
     */
    public function getId();

    public function setLogDate($logDate);

    /**
     * @return \DateTime
     */
    public function getLogDate();

    public function setRequestId($requestId);

    /**
     * @return string
     */
    public function getRequestId();

    public function setMessageId($messageId);

    /**
     * @return string
     */
    public function getMessageId();

    public function setRequestState($requestState);

    /**
     * @return string
     */
    public function getRequestState();

    public function setAction($action);

    /**
     * @return string
     */
    public function getAction();

    public function setRecoveryIndicator($recoveryIndicator);

    /**
     * @return string
     */
    public function getRecoveryIndicator();

    public function setRelationNumberKeeper($relationNumberKeeper);

    /**
     * @return string
     */
    public function getRelationNumberKeeper();

    public function setUbn($ubn);

    /**
     * @return string
     */
    public function getUbn();

    /**
     * @return string|null
     */
    public function getMessageNumberToRecover();

    public function setMessageNumberToRecover($messageNumberToRecover);

    /**
     * @return string
     */
    public function getActionBy();

    public function setActionBy($actionBy);

    /**
     * @return boolean
     */
    public function isHideFailedMessage();

    public function setHideFailedMessage($hideFailedMessage);

    /**
     * @return boolean
     */
    public function isHideForAdmin();

    public function setHideForAdmin($hideForAdmin);

    public function getNewestVersion();

    public function setNewestVersion($newestVersion);

    /**
     * @return boolean
     */
    public function isRvoMessage(): bool;

    public function setIsRvoMessage(bool $isRvoMessage);

    function setFinishedRequestState();

    function setFinishedWithWarningRequestState();

    function setFailedRequestState();

    function setRevokedRequestState();
}