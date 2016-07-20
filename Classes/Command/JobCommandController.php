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

use Flowpack\JobQueue\Common\Exception as JobQueueException;
use Flowpack\JobQueue\Common\Job\JobManager;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;

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
     * This command is used to execute jobs that are submitted to a queue.
     * It is meant to run in a "server loop" and should be backed by some Process Control System (e.g. supervisord) that
     * will restart the script if it died (due to exceptions or memory limits for example).
     *
     * Alternatively the <i>exit-after</i> flag can be used in conjunction with cron-jobs in order to manually (re)start
     * the worker after a given amount of time.
     *
     * With the <i>limit</i> flag the number of executed jobs can be limited before the script exits.
     * This can be combined with <i>exit-after</i> to exit when either the time or job limit is reached
     *
     * The <i>verbose</i> flag can be used to gain some insight about which jobs are executed etc.
     *
     * @param string $queue Name of the queue to fetch messages from. Can also be a comma-separated list of queues.
     * @param int $exitAfter If set, this command will exit after the given amount of seconds
     * @param int $limit If set, only the given amount of jobs are processed (successful or not) before the script exits
     * @param bool $verbose Output debugging information
     * @return void
     */
    public function workCommand($queue, $exitAfter = null, $limit = null, $verbose = false)
    {
        if ($verbose) {
            $this->output('Watching queue <b>"%s"</b>', [$queue]);
            if ($exitAfter !== null) {
                $this->output(' for <b>%d</b> seconds', [$exitAfter]);
            }
            $this->outputLine('...');
        }
        $startTime = time();
        $timeout = null;
        $numberOfJobExecutions = 0;
        do {
            $message = null;
            if ($exitAfter !== null) {
                $timeout = max(1, $exitAfter - (time() - $startTime));
            }
            try {
                $message = $this->jobManager->waitAndExecute($queue, $timeout);
            } catch (JobQueueException $exception) {
                $numberOfJobExecutions ++;
                $this->outputLine('<error>%s</error>', [$exception->getMessage()]);
                if ($verbose && $exception->getPrevious() instanceof \Exception) {
                    $this->outputLine('  Reason: %s', [$exception->getPrevious()->getMessage()]);
                }
            } catch (\Exception $exception) {
                $this->outputLine('<error>Unexpected exception during job execution: %s, aborting...</error>', [$exception->getMessage()]);
                $this->quit(1);
            }
            if ($message !== null) {
                $numberOfJobExecutions ++;
                if ($verbose) {
                    $messagePayload = strlen($message->getPayload()) <= 50 ? $message->getPayload() : substr($message->getPayload(), 0, 50) . '...';
                    $this->outputLine('<success>Successfully executed job "%s" (%s)</success>', [$message->getIdentifier(), $messagePayload]);
                }
            }
            if ($exitAfter !== null && (time() - $startTime) >= $exitAfter) {
                if ($verbose) {
                    $this->outputLine('Quitting after %d seconds due to <i>--exit-after</i> flag', [time() - $startTime]);
                }
                $this->quit();
            }
            if ($limit !== null && $numberOfJobExecutions >= $limit) {
                if ($verbose) {
                    $this->outputLine('Quitting after %d executed job%s due to <i>--limit</i> flag', [$numberOfJobExecutions, $numberOfJobExecutions > 1 ? 's' : '']);
                }
                $this->quit();
            }

        } while (true);
    }

    /**
     * List queued jobs
     *
     * Shows the label of the next <i>$limit</i> Jobs in a given queue.
     *
     * @param string $queue The name of the queue
     * @param integer $limit Number of jobs to list (some queues only support a limit of 1)
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
     * @param string $serializedMessage An instance of Message serialized and base64-encoded
     * @return void
     * @internal This command is mainly used by the JobManager and FakeQueue in order to execute commands in sub requests
     */
    public function executeCommand($queue, $serializedMessage)
    {
        /** @var Message $message */
        $message = unserialize(base64_decode($serializedMessage));
        $queue = $this->queueManager->getQueue($queue);
        $this->jobManager->executeJobForMessage($queue, $message);
    }
}
