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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Flowpack\JobQueue\Common\Exception as JobQueueException;
use Neos\Flow\Utility\Arrays;

/**
 * The Queue Manager, mainly used to instantiate QueueInterface instances
 *
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
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $queueSettingsRuntimeCache = [];

    /**
     * @var array
     */
    protected $queues = [];

    /**
     * Returns a queue with the specified $queueName
     *
     * @param string $queueName
     * @return QueueInterface
     * @throws JobQueueException
     * @api
     */
    public function getQueue($queueName)
    {
        if (isset($this->queues[$queueName])) {
            return $this->queues[$queueName];
        }

        $queueSettings = $this->getQueueSettings($queueName);

        if (!isset($queueSettings['className'])) {
            throw new JobQueueException(sprintf('Option className for queue "%s" is not configured', $queueName), 1334147126);
        }

        $queueObjectName = $queueSettings['className'];
        if (!class_exists($queueObjectName)) {
            throw new JobQueueException(sprintf('Configured class "%s" for queue "%s" does not exist', $queueObjectName, $queueName), 1445611607);
        }


        if (isset($queueSettings['queueNamePrefix'])) {
            $queueNameWithPrefix = $queueSettings['queueNamePrefix'] . $queueName;
        } else {
            $queueNameWithPrefix = $queueName;
        }
        $options = isset($queueSettings['options']) ? $queueSettings['options'] : [];
        $queue = new $queueObjectName($queueNameWithPrefix, $options);
        $this->queues[$queueName] = $queue;

        return $queue;
    }

    /**
     * Returns the settings for the requested queue, merged with the preset defaults if any
     *
     * @param string $queueName
     * @return array
     * @throws JobQueueException if no queue for the given $queueName is configured
     * @api
     */
    public function getQueueSettings($queueName)
    {
        if (isset($this->queueSettingsRuntimeCache[$queueName])) {
            return $this->queueSettingsRuntimeCache[$queueName];
        }
        if (!isset($this->settings['queues'][$queueName])) {
            throw new JobQueueException(sprintf('Queue "%s" is not configured', $queueName), 1334054137);
        }
        $queueSettings = $this->settings['queues'][$queueName];
        if (isset($queueSettings['preset'])) {
            $presetName = $queueSettings['preset'];
            if (!isset($this->settings['presets'][$presetName])) {
                throw new JobQueueException(sprintf('Preset "%s", referred to in settings for queue "%s" is not configured', $presetName, $queueName), 1466677893);
            }
            $queueSettings = Arrays::arrayMergeRecursiveOverrule($this->settings['presets'][$presetName], $queueSettings);
        }
        $this->queueSettingsRuntimeCache[$queueName] = $queueSettings;
        return $this->queueSettingsRuntimeCache[$queueName];
    }

}