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
 * Message object
 */
class Message
{
    // Created locally, not published to queue
    const STATE_NEW = 0;
    // Message published to queue, should not be processed by client
    const STATE_SUBMITTED = 1;
    // Message received from queue, not deleted from queue! (a.k.a. Reserved)
    const STATE_RESERVED = 2;
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
    public function __construct($payload, $identifier = null)
    {
        $this->payload = $payload;
        $this->identifier = $identifier;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'identifier' => $this->identifier,
            'payload' => $this->payload,
            'state' => $this->state
        );
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param integer $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getOriginalValue()
    {
        return $this->originalValue;
    }

    /**
     * @param string $originalValue
     */
    public function setOriginalValue($originalValue)
    {
        $this->originalValue = $originalValue;
    }
}
