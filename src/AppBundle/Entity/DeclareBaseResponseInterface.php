<?php


namespace AppBundle\Entity;


interface DeclareBaseResponseInterface
{
    public function getMessageNumber(): ?string;
    public function setMessageNumber(?string $messageNumber): DeclareBaseResponseInterface;

    public function setErrorCode(?string $errorCode): DeclareBaseResponseInterface;
    public function getErrorCode(): ?string;

    public function setErrorMessage(?string $errorMessage): DeclareBaseResponseInterface;
    public function getErrorMessage(): ?string;

    public function setErrorKindIndicator(?string $errorKindIndicator): DeclareBaseResponseInterface;
    public function getErrorKindIndicator(): ?string;

    public function setSuccessIndicator(?string $successIndicator): DeclareBaseResponseInterface;
    public function getSuccessIndicator(): ?string;

    public function setRequestId(string $requestId);
    public function getRequestId(): ?string;

    public function setIsRemovedByUser(bool $isRemovedByUser): DeclareBaseResponseInterface;
    public function isIsRemovedByUser(): bool;
    public function getIsRemovedByUser(): bool;

    public function setSuccessValues(): DeclareBaseResponseInterface;
    public function setFailedValues(string $errorMessage, string $errorCode): DeclareBaseResponseInterface;
    public function setWarningValues(string $errorMessage, string $errorCode): DeclareBaseResponseInterface;

    public function  hasSuccessResponse(): bool;
    public function  hasSuccessWithWarningResponse(): bool;
    public function  hasFailedResponse(): bool;
}