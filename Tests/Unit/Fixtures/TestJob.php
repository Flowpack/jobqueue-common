<?php
namespace Flowpack\JobQueue\Common\Tests\Unit\Fixtures;

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
use Flowpack\JobQueue\Common\Queue\QueueInterface;

/**
 * Test job
 */
class TestJob implements JobInterface
{
    /**
     * @var boolean
     */
    protected $processed = false;

    /**
     * Do nothing
     *
     * @param QueueInterface $queue
     * @param Message $message
     * @return boolean
     */
    public function execute(QueueInterface $queue, Message $message)
    {
        $this->processed = true;
        return true;
    }

    /**
     * @return boolean
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * Get an optional identifier for the job
     *
     * @return string A job identifier
     */
    public function getIdentifier()
    {
        return 'testjob';
    }

    /**
     * Get a readable label for the job
     *
     * @return string A label for the job
     */
    public function getLabel()
    {
        return 'Test Job';
    }
}
