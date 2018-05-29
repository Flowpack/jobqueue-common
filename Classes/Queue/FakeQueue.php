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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Utility\Algorithms;

/**
 * A very basic queue that immediately dispatches messages upon submission.
 *
 * This queue is only meant as "poor man solution" for scenarios where using a proper queue is not an option or unnecessary.
 */
class FakeQueue implements QueueInterface
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array
     */
    protected $flowSettings;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $async = false;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $messageCache;

    /**
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options = [])
    {
        $this->name = $name;
        if (isset($options['async']) && $options['async'] === true) {
            $this->async = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        // The FakeQueue does not require any setup but we use it to verify the options
        if ($this->async && !method_exists(Scripts::class, 'executeCommandAsync')) {
            throw new \RuntimeException('The "async" flag is set, but the currently used Flow version doesn\'t support this (Flow 3.3+ is required)', 1468940734);
        }
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
        $messageId = Algorithms::generateUUID();
        $message = new Message($messageId, $payload);

        $messageCacheIdentifier = sha1(serialize($message));
        $this->messageCache->set($messageCacheIdentifier, serialize($message));

        if ($this->async) {
            Scripts::executeCommandAsync('flowpack.jobqueue.common:job:execute', $this->flowSettings, [$this->name, $messageCacheIdentifier]);
        } else {
            Scripts::executeCommand('flowpack.jobqueue.common:job:execute', $this->flowSettings, true, [$this->name, $messageCacheIdentifier]);
        }
        return $messageId;
    }

    /**
     * @inheritdoc
     */
    public function waitAndTake($timeout = null)
    {
        throw new \BadMethodCallException('The FakeQueue does not support reserving of messages.' . chr(10) . 'It is not required to use a worker for this queue as messages are handled immediately upon submission.', 1468425275);
    }

    /**
     * @inheritdoc
     */
    public function waitAndReserve($timeout = null)
    {
        throw new \BadMethodCallException('The FakeQueue does not support reserving of messages.' . chr(10) . 'It is not required to use a worker for this queue as messages are handled immediately upon submission.', 1468425280);
    }

    /**
     * @inheritdoc
     */
    public function release($messageId, array $options = [])
    {
        throw new \BadMethodCallException('The FakeQueue does not support releasing of failed messages.' . chr(10) . 'The "maximumNumberOfReleases" setting should be removed or set to 0 for this queue!', 1468425285);
    }

    /**
     * @inheritdoc
     */
    public function abort($messageId)
    {
        // The FakeQueue does not support message abortion
    }

    /**
     * @inheritdoc
     */
    public function finish($messageId)
    {
        // The FakeQueue does not support message finishing
    }

    /**
     * @inheritdoc
     */
    public function peek($limit = 1)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function countReady(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function countReserved(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function countFailed(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        // The FakeQueue does not support message flushing
    }

}
