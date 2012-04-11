<?php
namespace Jobqueue\Common\Queue;

/*                                                                        *
 * This script belongs to the FLOW3 package "Jobqueue.Common".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Message object
 */
class Message {

	// Created locally, not published to queue
	const STATE_NEW = 0;
	// Message published to queue, should not be processed by client
	// TODO Rename _SUBMITTED
	const STATE_PUBLISHED = 1;
	// Message received from queue, not deleted from queue! (a.k.a. Reserved)
	// TODO Rename _RESERVED
	const STATE_RECEIVED = 2;
	// Message processed and deleted from queue
	const STATE_DONE = 3;

	/**
	 * Depending on the queue implementation, this identifier will
	 * allow for unique messages (e.g. prevent adding jobs twice).
	 *
	 * @var string Identifier of the message
	 */
	protected $identifier;

	/**
	 * The message payload has to be serializable.
	 *
	 * @var mixed The message payload
	 */
	protected $payload;

	/**
	 * @var integer State of the message, one of the Message::STATE_* constants
	 */
	protected $state = self::STATE_NEW;

	/**
	 * @var string The original message value as encoded in a queue
	 * @todo Can be removed with new Redis implementation
	 */
	protected $originalValue;

	/**
	 * Constructor
	 *
	 * @param mixed $payload
	 * @param string $identifier
	 */
	public function __construct($payload, $identifier = NULL) {
		$this->payload = $payload;
		$this->identifier = $identifier;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return array(
			'identifier' => $this->identifier,
			'payload' => $this->payload,
			'state' => $this->state
		);
	}

	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param mixed $payload
	 */
	public function setPayload($payload) {
		$this->payload = $payload;
	}

	/**
	 * @return mixed
	 */
	public function getPayload() {
		return $this->payload;
	}

	/**
	 * @param integer $state
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * @return integer
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @return string
	 */
	public function getOriginalValue() {
		return $this->originalValue;
	}

	/**
	 * @param string $originalValue
	 */
	public function setOriginalValue($originalValue) {
		$this->originalValue = $originalValue;
	}

}
?>