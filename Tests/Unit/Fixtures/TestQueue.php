<?php
namespace Flowpack\JobQueue\Common\Tests\Unit\Fixtures;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use Neos\Flow\Utility\Algorithms;

/**
 * Test queue
 *
 * A simple in-memory message queue for unit tests.
 */
class TestQueue implements QueueInterface
{
    /**
     * @var string[]
     */
    protected $readyMessages = [];

    /**
     * @var string[]
     */
    protected $reservedMessages = [];

    /**
     * @var string[]
     */
    protected $failedMessages = [];

    /**
     * @var string[]
     */
    protected $processingMessages = [];

    /**
     * @var int[]
     */
    protected $numberOfReleases = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var int
     */
    protected $defaultTimeout = 60;

    /**
     * @var array
     */
    protected $lastSubmitOptions;

    /**
     * @var array
     */
    protected $lastReleaseOptions;

  /**
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options = [])
    {
        $this->name = $name;
        if (isset($options['defaultTimeout'])) {
            $this->defaultTimeout = (integer)$options['defaultTimeout'];
        }
        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        // The TestQueue does not require any setup
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function submit($payload, array $options = [])
    {
        $this->lastSubmitOptions = $options;
        $messageId = Algorithms::generateUUID();
        $this->readyMessages[$messageId] = $payload;
        return $messageId;
    }

    /**
     * @inheritdoc
     */
    public function waitAndTake($timeout = null)
    {
        $message = $this->reserveMessage($timeout);
        if ($message === null) {
            return null;
        }
        unset($this->processingMessages[$message->getIdentifier()]);
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function waitAndReserve($timeout = null)
    {
        return $this->reserveMessage($timeout);
    }

    /**
     * @param integer $timeout
     * @return Message
     */
    protected function reserveMessage($timeout = null)
    {
        if ($timeout === null) {
            $timeout = $this->defaultTimeout;
        }
        $startTime = time();
        do {
            $nextMessageIdAndPayload = array_slice($this->readyMessages, 0, 1);
            if (time() - $startTime >= $timeout) {
                return null;
            }
        } while ($nextMessageIdAndPayload === []);
        $messageId = key($nextMessageIdAndPayload);
        $payload = $nextMessageIdAndPayload[$messageId];
        unset($this->readyMessages[$messageId]);
        $this->processingMessages[$messageId] = $nextMessageIdAndPayload[$messageId];

        $numberOfReleases = isset($this->numberOfReleases[$messageId]) ? $this->numberOfReleases[$messageId] : 0;
        return new Message($messageId, $payload, $numberOfReleases);
    }

    /**
     * @inheritdoc
     */
    public function release($messageId, array $options = [])
    {
        $this->lastReleaseOptions = $options;
        if (!isset($this->processingMessages[$messageId])) {
            return;
        }
        $payload = $this->processingMessages[$messageId];
        $this->numberOfReleases[$messageId] = isset($this->numberOfReleases[$messageId]) ? $this->numberOfReleases[$messageId] + 1 : 1;
        unset($this->processingMessages[$messageId]);
        $this->readyMessages[$messageId] = $payload;
    }

    /**
     * @inheritdoc
     */
    public function abort($messageId)
    {
        if (!isset($this->readyMessages[$messageId])) {
            return;
        }
        $this->failedMessages[$messageId] = $this->readyMessages[$messageId];
        unset($this->readyMessages[$messageId]);
    }

    /**
     * @inheritdoc
     */
    public function finish($messageId)
    {
        unset($this->processingMessages[$messageId]);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function peek($limit = 1)
    {
        $messageIdsAndPayload = array_slice($this->readyMessages, 0, $limit);
        $messages = [];
        foreach ($messageIdsAndPayload as $messageId => $payload) {
            $messages[] = new Message($messageId, $payload);
        }
        return $messages;
    }

    /**
     * @inheritdoc
     */
    public function countReady():int
    {
        return count($this->readyMessages);
    }

    /**
     * @inheritdoc
     */
    public function countReserved(): int
    {
        return count($this->reservedMessages);
    }

    /**
     * @inheritdoc
     */
    public function countFailed(): int
    {
        return count($this->failedMessages);
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        $this->readyMessages = $this->processingMessages = $this->failedMessages = $this->numberOfReleases = [];
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
    public function getLastSubmitOptions()
    {
        return $this->lastSubmitOptions;
    }

    /**
     * @return array
     */
    public function getLastReleaseOptions()
    {
        return $this->lastReleaseOptions;
    }

}
