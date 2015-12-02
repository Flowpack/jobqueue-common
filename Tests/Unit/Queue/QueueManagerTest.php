<?php
namespace TYPO3\Jobqueue\Common\Tests\Unit\Queue;

/*
 * This file is part of the TYPO3.Jobqueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Jobqueue\Common\Queue\QueueManager;
use TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue;

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
                    'className' => 'TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
                )
            )
        ));

        $queue = $queueManager->getQueue('TestQueue');
        $this->assertInstanceOf('TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue', $queue);
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
                    'className' => 'TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue',
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
                    'className' => 'TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
                )
            )
        ));

        $queue = $queueManager->getQueue('TestQueue');
        $this->assertSame($queue, $queueManager->getQueue('TestQueue'));
    }
}
