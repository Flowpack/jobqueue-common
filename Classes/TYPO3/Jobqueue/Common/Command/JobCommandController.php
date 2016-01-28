<?php
namespace TYPO3\Jobqueue\Common\Command;

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
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Jobqueue\Common\Exception as JobQueueException;
use TYPO3\Jobqueue\Common\Job\JobManager;
use TYPO3\Jobqueue\Common\Queue\QueueManager;

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
     * @param string $queueName The name of the queue
     * @param integer $limit The max number of jobs that should execute before exiting.
     * @return void
     */
    public function workCommand($queueName, $limit = 0)
    {
        $runInfiniteJobs = ($limit === 0 || $limit < 0);
        $jobsDone = 0;
        do {
            try {
                $jobsDone++;
                $this->jobManager->waitAndExecute($queueName);
            } catch (JobQueueException $exception) {
                $this->outputLine($exception->getMessage());
                if ($exception->getPrevious() instanceof \Exception) {
                    $this->outputLine($exception->getPrevious()->getMessage());
                }
            } catch (\Exception $exception) {
                $this->outputLine('Unexpected exception during job execution: %s', array($exception->getMessage()));
            }
        } while ($runInfiniteJobs || $jobsDone < $limit);
    }

    /**
     * List queued jobs
     *
     * @param string $queueName The name of the queue
     * @param integer $limit Number of jobs to list
     * @return void
     */
    public function listCommand($queueName, $limit = 1)
    {
        $jobs = $this->jobManager->peek($queueName, $limit);
        $totalCount = $this->queueManager->getQueue($queueName)->count();
        foreach ($jobs as $job) {
            $this->outputLine('<u>%s</u>', array($job->getLabel()));
        }

        if ($totalCount > count($jobs)) {
            $this->outputLine('(%d omitted) ...', array($totalCount - count($jobs)));
        }
        $this->outputLine('(<b>%d total</b>)', array($totalCount));
    }
}
