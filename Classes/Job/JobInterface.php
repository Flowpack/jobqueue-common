<?php
namespace Flowpack\JobQueue\Common\Job;

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
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;

/**
 * Job interface
 */
interface JobInterface
{
    /**
     * Execute the job
     *
     * A job should finish itself after successful execution using the queue methods.
     *
     * @param QueueInterface $queue
     * @param Message $message The original message
     * @return boolean TRUE if the job was executed successfully and the message should be finished
     */
    public function execute(QueueInterface $queue, Message $message);

    /**
     * Get an optional identifier for the job
     *
     * @return string A job identifier
     */
    public function getIdentifier();

    /**
     * Get a readable label for the job
     *
     * @return string A label for the job
     */
    public function getLabel();
}
