<?php
namespace Flowpack\JobQueue\Common\Queue;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Message queue interface
 */
interface QueueInterface
{
    /**
     * @return void
     */
    public function setUp(): void;

    /**
     * The unique name of this queue
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Submit a message to the queue
     *
     * @param mixed $payload
     * @param array $options Simple key/value array with options, supported options depend on the queue implementation
     * @return string The identifier of the message under which it was queued
     */
    public function submit($payload, array $options = []): string;

    /**
     * Wait for a message in the queue and remove the message from the queue for processing
     * If a non-null value was returned, the message was not queued. Otherwise a timeout
     * occurred and no message was available or received.
     *
     * @param int $timeout
     * @return Message The received message or NULL if a timeout occurred
     */
    public function waitAndTake(?int $timeout = null): ?Message;

    /**
     * Wait for a message in the queue and reserve the message for processing
     * NOTE: The processing of the message has to be confirmed by the consumer to
     * remove the message from the queue by calling finish(). Depending on the implementation
     * the message might be inserted to the queue after some time limit has passed.
     * If a non-null value was returned, the message was reserved. Otherwise a timeout
     * occurred and no message was available or received.
     *
     * @param int $timeout
     * @return Message The received message or NULL if a timeout occurred
     */
    public function waitAndReserve(?int $timeout = null): ?Message;

    /**
     * Puts a reserved message back to the queue
     *
     * @param string $messageId
     * @param array $options Simple key/value array with options that can be interpreted by the concrete implementation (optional)
     * @return void
     */
    public function release(string $messageId, array $options = []): void;

    /**
     * Removes a message from the active queue and marks it failed (bury)
     *
     * @param string $messageId
     * @return void
     */
    public function abort(string $messageId): void;

    /**
     * Mark a message as done
     *
     * This must be called for every message that was reserved and that was
     * processed successfully.
     *
     * @param string $messageId
     * @return bool TRUE if the message could be removed
     */
    public function finish(string $messageId): bool;

    /**
     * Peek for messages
     *
     * Inspect the next messages without taking them from the queue. It is not safe to take the messages
     * and process them, since another consumer could have received this message already!
     *
     * @param integer $limit
     * @return Message[] The messages up to the length of limit or an empty array if no messages are present currently
     */
    public function peek(int $limit = 1): array;

    /**
     * Get a count of ready messages currently in the queue.
     *
     * @return int The number of ready messages in the queue
     */
    public function countReady(): int;

    /**
     * Get a count of reserved messages currently in the queue.
     *
     * @return int The number of reserved messages in the queue
     */
    public function countReserved(): int;

    /**
     * Get a count of failed messages currently in the queue.
     *
     * @return int The number of failed messages in the queue
     */
    public function countFailed(): int;

    /**
     * Removes all messages from this queue
     *
     * Danger, all queued items will be lost!
     * This is a method primarily used in testing, not part of the API.
     *
     * @return void
     */
    public function flush(): void;
}
