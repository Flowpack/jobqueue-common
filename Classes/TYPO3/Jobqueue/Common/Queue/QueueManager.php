<?php
namespace TYPO3\Jobqueue\Common\Queue;

/*
 * This file is part of the TYPO3.Jobqueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Jobqueue\Common\Exception as JobQueueException;

/**
 * Queue manager
 * @Flow\Scope("singleton")
 */
class QueueManager
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $queues = array();

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     *
     * @param string $queueName
     * @return QueueInterface
     * @throws JobQueueException
     */
    public function getQueue($queueName)
    {
        if (isset($this->queues[$queueName])) {
            return $this->queues[$queueName];
        }

        if (!isset($this->settings['queues'][$queueName])) {
            throw new JobQueueException(sprintf('Queue "%s" is not configured', $queueName), 1334054137);
        }
        if (!isset($this->settings['queues'][$queueName]['className'])) {
            throw new JobQueueException(sprintf('Option className for queue "%s" is not configured', $queueName), 1334147126);
        }

        $queueObjectName = $this->settings['queues'][$queueName]['className'];
        if (!class_exists($queueObjectName)) {
            throw new JobQueueException(sprintf('Configured class "%s" for queue "%s" does not exist', $queueObjectName, $queueName), 1445611607);
        }

        $options = isset($this->settings['queues'][$queueName]['options']) ? $this->settings['queues'][$queueName]['options'] : array();
        $queue = new $queueObjectName($queueName, $options);

        $this->queues[$queueName] = $queue;

        return $queue;
    }
}
