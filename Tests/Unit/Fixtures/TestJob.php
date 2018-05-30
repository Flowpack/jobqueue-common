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
     * @var int How often the job execution should fail
     */
    protected $failNumberOfTimes;

    /**
     * @param int $failNumberOfTimes How often should this job fail before it returns true in execute()
     */
    public function __construct($failNumberOfTimes = 0)
    {
        $this->failNumberOfTimes = $failNumberOfTimes;
    }

    /**
     * Do nothing
     *
     * @param QueueInterface $queue
     * @param Message $message
     * @return bool
     */
    public function execute(QueueInterface $queue, Message $message): bool
    {
        if ($this->failNumberOfTimes > $message->getNumberOfReleases()) {
            return false;
        }
        return true;
    }

    /**
     * Get a readable label for the job
     *
     * @return string A label for the job
     */
    public function getLabel(): string
    {
        return 'Test Job';
    }
}
