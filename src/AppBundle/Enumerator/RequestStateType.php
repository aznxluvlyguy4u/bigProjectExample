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
    const OPEN = "open";
    const FINISHED = "finished";
    const FAILED = "failed";
    const CANCELLED = "cancelled";
    const REVOKED = "revoked";

}