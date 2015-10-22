<?php
namespace TYPO3\Jobqueue\Common\Tests\Unit\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Jobqueue.Common". *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Jobqueue\Common\Queue\Message;
use TYPO3\Jobqueue\Common\Queue\QueueInterface;

/**
 * Test queue
 *
 * A simple in-memory message queue for unit tests.
 */
class TestQueue implements QueueInterface
{
    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @var array
     */
    protected $processing = array();

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options;

    /**
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options)
    {
        $this->name = $name;
        $this->options = $options;
    }

        /**
     * @param Message $message
     * @return void
     */
    public function finish(Message $message)
    {
        unset($this->processing[$message->getIdentifier()]);
    }

    /**
     * @param integer $limit
     * @return Message
     */
    public function peek($limit = 1)
    {
        return count($this->messages) > 0 ? $this->messages[0] : null;
    }

    /**
     * @param Message $message
     * @return void
     */
    public function publish(Message $message)
    {
        // TODO Unique identifiers
        $this->messages[] = $message;
    }

    /**
     * @param integer $timeout
     * @return Message
     */
    public function waitAndReserve($timeout = 60)
    {
        $message = array_shift($this->messages);
        if ($message !== null) {
            $this->processing[$message->getIdentifier()] = $message;
        }
        return $message;
    }

    /**
     *
     * @param integer $timeout
     * @return Message
     */
    public function waitAndTake($timeout = 60)
    {
        $message = array_shift($this->messages);
        return $message;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return array
     */
    public function getProcessing()
    {
        return $this->processing;
    }

    /**
     * @return integer
     */
    public function count()
    {
        return count($this->messages);
    }

    /**
     *
     * @param string $identifier
     * @return Message
     */
    public function getMessage($identifier)
    {
        return null;
    }
}
