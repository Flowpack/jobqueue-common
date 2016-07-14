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

use Flowpack\JobQueue\Common\Job\JobInterface;
use Flowpack\JobQueue\Common\Queue\Message;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use Flowpack\JobQueue\Common\Exception as JobQueueException;
use Flowpack\JobQueue\Common\Job\JobManager;
use Flowpack\JobQueue\Common\Queue\QueueManager;
use TYPO3\Flow\Utility\Arrays;

/**
 * Job command controller
 */
class JobCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @Flow\Inject
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * Work on a queue and execute jobs
     *
     * @param string $queue Name of the queue to fetch messages from. Can also be a comma-separated list of queues.
     * @param boolean $verbose
     * @return void
     */
    public function workCommand($queue, $verbose = false)
    {
        $job = null;
        if (strpos($queue, ',') === false) {
            $queues = [ $queue ];
        } else {
            $queues = Arrays::trimExplode(',', $queue);
        }

        if ($verbose) {
            $this->outputLine('Watching queue%s %s ...', [count($queues) > 1 ? 's' : '', implode(', ', $queues)]);
        }

        $timeout = (count($queues) > 1 ? 5 : null);
        do {
            foreach ($queues as $queue) {
                try {
                    $job = $this->jobManager->waitAndExecute($queue, $timeout);
                } catch (JobQueueException $exception) {
                    $this->outputLine($exception->getMessage());
                    if ($exception->getPrevious() instanceof \Exception) {
                        $this->outputLine('<error>%s</error>', [$exception->getPrevious()->getMessage()]);
                    }
                } catch (\Exception $exception) {
                    $this->outputLine('%s: <error>Unexpected exception during job execution: %s</error>', [$queue, $exception->getMessage()]);
                }
                if ($verbose) {
                    if ($job !== null) {
                        $this->outputLine("%s: Successfully executed job '%s'", [$queue, $job->getLabel()]);
                    }
                }
            }
        } while (true);
    }

    /**
     * List queued jobs
     *
     * @param string $queue The name of the queue
     * @param integer $limit Number of jobs to list
     * @return void
     */
    public function listCommand($queue, $limit = 1)
    {
        $jobs = $this->jobManager->peek($queue, $limit);
        $totalCount = $this->queueManager->getQueue($queue)->count();
        foreach ($jobs as $job) {
            $this->outputLine('<b>%s</b>', [$job->getLabel()]);
        }

        if ($totalCount > count($jobs)) {
            $this->outputLine('(%d omitted) ...', [$totalCount - count($jobs)]);
        }
        $this->outputLine('(<b>%d total</b>)', [$totalCount]);
    }

    /**
     * Execute one job
     *
     * @param string $queue
     * @param string $serializedJob An instance of JobInterface serialized and base64-encoded
     * @return void
     * @internal This command is mainly needed for the SerialQueue in order to execute commands in sub requests
     */
    public function executeCommand($queue, $serializedJob)
    {
        $queue = $this->queueManager->getQueue($queue);
        $job = unserialize(base64_decode($serializedJob));
        if (!$job instanceof JobInterface) {
            throw new \RuntimeException('Argument could not be unserialized to a class implementing JobInterface', 1465901250);
        }
        $message = new Message(null, $serializedJob);
        $job->execute($queue, $message);
    }
}
