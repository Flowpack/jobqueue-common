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
use TYPO3\Flow\Core\Booting\Scripts;
use TYPO3\Flow\Utility\Algorithms;

class SerialQueue implements QueueInterface
{
    /**
     * @Flow\InjectConfiguration(package="TYPO3.Flow")
     * @var array
     */
    protected $flowSettings;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options = [])
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        // The SynchronousQueue does not require any setup
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
        $commandArguments = ['queueName' => $this->name, 'serializedJob' => base64_encode($payload)];
        Scripts::executeCommandAsync('flowpack.jobqueue.common:job:execute', $this->flowSettings, $commandArguments);
        return $messageId;
    }

    /**
     * @inheritdoc
     */
    public function waitAndTake($timeout = null)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function waitAndReserve($timeout = null)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function release($messageId, array $options = [])
    {
    }

    /**
     * @inheritdoc
     */
    public function abort($messageId)
    {
    }

    /**
     * @inheritdoc
     */
    public function finish($messageId)
    {
        return false;
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
    public function count()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        //
    }

}
