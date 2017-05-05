<?php

namespace AppBundle\Enumerator;

/**
 * Class RequestStateType
 *
 * Enum to set for Requests state.
 *
 * OPEN = Successfully send message to Queue.
 * FAILED = Failed to send message to Queue, thus needs to retry sending.
 * FINISHED = Successfully send message to Queue & received response that is (successfully) persisted to the database.
 * CANCELLED = Cancel a request with state open.
 *
 * @package AppBundle\Enumerator
 */
class RequestStateType
{
    const OPEN = "OPEN";
    const COMPLETED = "COMPLETED";
    const FINISHED = "FINISHED";
    const FINISHED_WITH_WARNING = "FINISHED_WITH_WARNING";
    const FAILED = "FAILED";
    const CANCELLED = "CANCELLED";
    const REJECTED = "REJECTED";
    const REVOKED = "REVOKED";
    const REVOKING = "REVOKING";
    const IMPORTED = "IMPORTED";
}