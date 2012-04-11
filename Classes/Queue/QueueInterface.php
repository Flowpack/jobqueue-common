<?php
namespace Jobqueue\Common\Queue;

/*                                                                        *
 * This script belongs to the FLOW3 package "Jobqueue.Common".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Message queue interface
 */
interface QueueInterface {

	/**
	 * Publish a message to the queue
	 *
	 * The state of the message will be updated according
	 * to the result of the operation.
	 *
	 * If the queue supports unique messages, the message should not be queued if
	 * another message with the same identifier already exists.
	 *
	 * @param \Jobqueue\Common\Queue\Message $message
	 * @return string The identifier of the message under which it was queued
	 * @todo rename to submit()
	 */
	public function publish(\Jobqueue\Common\Queue\Message $message);

	/**
	 * Wait for a message in the queue and remove the message from the queue for processing
	 *
	 * If a non-null value was returned, the message was unqueued. Otherwise a timeout
	 * occured and no message was available or received.
	 *
	 * @param integer $timeout
	 * @return \Jobqueue\Common\Queue\Message The received message or NULL if a timeout occured
	 */
	public function waitAndTake($timeout = NULL);

	/**
	 * Wait for a message in the queue and reserve the message for processing
	 *
	 * NOTE: The processing of the message has to be confirmed by the consumer to
	 * remove the message from the queue by calling finish(). Depending on the implementation
	 * the message might be inserted to the queue after some time limit has passed.
	 *
	 * If a non-null value was returned, the message was reserved. Otherwise a timeout
	 * occured and no message was available or received.
	 *
	 * @param integer $timeout
	 * @return \Jobqueue\Common\Queue\Message The received message or NULL if a timeout occured
	 */
	public function waitAndReserve($timeout = NULL);

	/**
	 * Mark a message as done
	 *
	 * This must be called for every message that was reserved and that was
	 * processed successfully.
	 *
	 * @return boolean TRUE if the message could be removed
	 */
	public function finish(\Jobqueue\Common\Queue\Message $message);

	/**
	 * Peek for messages
	 *
	 * Inspect the next messages without taking them from the queue. It is not safe to take the messages
	 * and process them, since another consumer could have received this message already!
	 *
	 * @param integer $limit
	 * @return array<\Jobqueue\Common\Queue\Message> The messages up to the length of limit or an empty array if no messages are present currently
	 */
	public function peek($limit = 1);

	/**
	 * Get a message by identifier
	 *
	 * @param string $identifier
	 * @return \Jobqueue\Common\Queue\Message The message or NULL if not present
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
?>