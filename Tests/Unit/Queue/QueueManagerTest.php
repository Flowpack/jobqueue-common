<?php
namespace Flowpack\JobQueue\Common\Tests\Unit\Queue;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use Flowpack\JobQueue\Common\Queue\QueueManager;
use Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue;

/**
 * Queue manager
 */
class QueueManagerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getQueueCreatesInstanceByQueueName()
    {
        $queueManager = new QueueManager();
        $queueManager->injectSettings(array(
            'queues' => array(
                'TestQueue' => array(
                    'className' => 'Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue'
                )
            )
        ));

        /** @var TestQueue $queue */
        $queue = $queueManager->getQueue('TestQueue');
        $this->assertInstanceOf('Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue', $queue);
        $this->assertSame('TestQueue', $queue->getName());
    }

    /**
     * @test
     */
    public function getQueueSetsOptionsOnInstance()
    {
        $queueManager = new QueueManager();
        $queueManager->injectSettings(array(
            'queues' => array(
                'TestQueue' => array(
                    'className' => 'Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue',
                    'options' => array(
                        'foo' => 'bar'
                    )
                )
            )
        ));

        /** @var TestQueue $queue */
        $queue = $queueManager->getQueue('TestQueue');
        $this->assertEquals(array('foo' => 'bar'), $queue->getOptions());
    }

    /**
     * @test
     */
    public function getQueueReusesInstances()
    {
        $queueManager = new QueueManager();
        $queueManager->injectSettings(array(
            'queues' => array(
                'TestQueue' => array(
                    'className' => 'Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue'
                )
            )
        ));

        $queue = $queueManager->getQueue('TestQueue');
        $this->assertSame($queue, $queueManager->getQueue('TestQueue'));
    }

    /**
     * @test
     */
    public function queuePrefixIsProperlyUsed()
    {
        $queueManager = new QueueManager();
        $queueManager->injectSettings(array(
            'queueNamePrefix' => 'specialQueue',
            'queues' => array(
                'TestQueue' => array(
                    'className' => 'Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue'
                )
            )
        ));

        /** @var TestQueue $queue */
        $queue = $queueManager->getQueue('TestQueue');
        $this->assertSame('specialQueueTestQueue', $queue->getName());
    }
}
