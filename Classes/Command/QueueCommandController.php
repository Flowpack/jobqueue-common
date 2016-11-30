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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Utility\TypeHandling;

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
     * Displays all configured queues, their type and the number of messages that are ready to be processed.
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
     * Describe a single queue
     *
     * Displays the configuration for a queue, merged with the preset settings if any.
     *
     * @param string $queue Name of the queue to describe (e.g. "some-queue")
     * @return void
     */
    public function describeCommand($queue)
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
     * Initialize a queue
     *
     * Checks connection to the queue backend and sets up prerequisites (e.g. required database tables)
     * Most queue implementations don't need to be initialized explicitly, but it doesn't harm and might help to find misconfigurations
     *
     * @param string $queue Name of the queue to initialize (e.g. "some-queue")
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
     * Remove all messages from a queue!
     *
     * This command will delete <u>all</u> messages from the given queue.
     * Thus it should only be used in tests or with great care!
     *
     * @param string $queue Name of the queue to flush (e.g. "some-queue")
     * @param bool $force This flag is required in order to avoid accidental flushes
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
        $this->outputLine('<success>Flushed queue "%s".</success>', [$queue->getName()]);
    }

    /**
     * Submit a message to a given queue
     *
     * This command can be used to "manually" add messages to a given queue.
     *
     * <b>Example:</b>
     * <i>flow queue:submit some-queue "some payload" --options '{"delay": 14}'</i>
     *
     * To make this work with the <i>JobManager</i> the payload has to be a serialized
     * instance of an object implementing <i>JobInterface</i>.
     *
     * @param string $queue Name of the queue to submit a message to (e.g. "some-queue")
     * @param string $payload Arbitrary payload, for example a serialized instance of a class implementing JobInterface
     * @param string $options JSON encoded, for example '{"some-option": "some-value"}'
     * @return void
     */
    public function submitCommand($queue, $payload, $options = null)
    {
        $queue = $this->queueManager->getQueue($queue);
        if ($options !== null) {
            $options = json_decode($options, true);
        }
        $messageId = $queue->submit($payload, $options !== null ? $options : []);
        $this->outputLine('<success>Submitted payload to queue "%s" with ID "%s".</success>', [$queue->getName(), $messageId]);
    }

}
