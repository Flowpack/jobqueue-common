<?php
namespace TYPO3\Jobqueue\Common\Tests\Unit\Fixtures;

/*
 * This file is part of the TYPO3.Jobqueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
    public function submit(Message $message)
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
