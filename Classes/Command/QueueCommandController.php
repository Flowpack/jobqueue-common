<?php
namespace Flowpack\JobQueue\Common\Command;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\JobQueue\Common\Queue\QueueManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * CLI controller to manage message queues
 */
class QueueCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * @Flow\InjectConfiguration(path="queues")
     * @var array
     */
    protected $queueConfigurations;

    /**
     * List configured queues
     *
     * @return void
     */
    public function listCommand()
    {
        $rows = [];
        foreach ($this->queueConfigurations as $queueName => $queueConfiguration) {
            $queue = $this->queueManager->getQueue($queueName);
            try {
                $numberOfMessages = $queue->count();
            } catch (\Exception $e) {
                $numberOfMessages = '-';
            }
            $rows[] = [$queue->getName(), TypeHandling::getTypeForValue($queue), $numberOfMessages];
        }
        $this->output->outputTable($rows, ['Queue', 'Type', '# messages']);
    }

    /**
     * Show details of a queue
     *
     * @param string $queue
     * @return void
     */
    public function showCommand($queue)
    {
        $queueSettings = $this->queueManager->getQueueSettings($queue);
        $this->outputLine('Configuration options for Queue <b>%s</b>:', [$queue]);
        $rows = [];
        foreach ($queueSettings as $name => $value) {
            $rows[] = [$name, is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value];
        }
        $this->output->outputTable($rows, ['Option', 'Value']);
    }

    /**
     * Initializes a queue
     *
     * @param string $queue
     * @return void
     */
    public function setupCommand($queue)
    {
        $queue = $this->queueManager->getQueue($queue);
        try {
            $queue->setUp();
        } catch (\Exception $exception) {
            $this->outputLine('<error>An error occurred while trying to setup queue "%s":</error>', [$queue->getName()]);
            $this->outputLine('%s (#%s)', [$exception->getMessage(), $exception->getCode()]);
            $this->quit(1);
        }
        $this->outputLine('<success>Queue "%s" has been initialized successfully.</success>', [$queue->getName()]);
    }

    /**
     * Removes all messages from a queue!
     *
     * @param string $queue
     * @param bool $force
     * @return void
     */
    public function flushCommand($queue, $force = false)
    {
        $queue = $this->queueManager->getQueue($queue);
        if (!$force) {
            $this->outputLine('Use the --force flag if you really want to flush queue "%s"', [$queue->getName()]);
            $this->outputLine('<error>Warning: This will delete all messages from the queue!</error>');
            $this->quit(1);
        }
        $queue->flush();
        $this->outputLine('Flushed queue "%s".', [$queue->getName()]);
    }

    /**
     * Submit a message to a given queue
     *
     * @param string $queue
     * @param string $payload
     * @param string $options JSON encoded
     * @return void
     */
    public function submitCommand($queue, $payload, $options = null)
    {
        $queue = $this->queueManager->getQueue($queue);
        if ($options !== null) {
            $options = json_decode($options, true);
        }
        $messageId = $queue->submit($payload, $options !== null ? $options : []);
        $this->outputLine('Submitted payload to queue "%s" with ID "%s".', [$queue->getName(), $messageId]);
    }

}
