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

use TYPO3\Flow\Annotations as Flow;

/**
 * Message queue interface
 */
interface QueueInterface
{
    /**
     * Publish a message to the queue
     * The state of the message will be updated according
     * to the result of the operation.
     * If the queue supports unique messages, the message should not be queued if
     * another message with the same identifier already exists.
     *
     * @param Message $message
     * @return string The identifier of the message under which it was queued
     */
    public function submit(Message $message);

    /**
     * Wait for a message in the queue and remove the message from the queue for processing
     * If a non-null value was returned, the message was unqueued. Otherwise a timeout
     * occured and no message was available or received.
     *
     * @param integer $timeout
     * @return Message The received message or NULL if a timeout occurred
     */
    public function waitAndTake($timeout = null);

    /**
     * Wait for a message in the queue and reserve the message for processing
     * NOTE: The processing of the message has to be confirmed by the consumer to
     * remove the message from the queue by calling finish(). Depending on the implementation
     * the message might be inserted to the queue after some time limit has passed.
     * If a non-null value was returned, the message was reserved. Otherwise a timeout
     * occurred and no message was available or received.
     *
     * @param integer $timeout
     * @return Message The received message or NULL if a timeout occurred
     */
    public function waitAndReserve($timeout = null);

    /**
     * Mark a message as done
     *
     * This must be called for every message that was reserved and that was
     * processed successfully.
     *
     * @param Message $message
     * @return boolean TRUE if the message could be removed
     */
    public function finish(Message $message);

    /**
     * Peek for messages
     *
     * Inspect the next messages without taking them from the queue. It is not safe to take the messages
     * and process them, since another consumer could have received this message already!
     *
     * @param integer $limit
     * @return array<\Flowpack\JobQueue\Common\Queue\Message> The messages up to the length of limit or an empty array if no messages are present currently
     */
    public function peek($limit = 1);

    /**
     * Get a message by identifier
     *
     * @param string $identifier
     * @return Message The message or NULL if not present
     */
    public function getMessage($identifier);

    /**
     * Count messages in the queue
     *
     * Get a count of messages currently in the queue.
     *
     * @return integer The number of messages in the queue
     */
    public function count();
}
