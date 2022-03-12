<?php
namespace Flowpack\JobQueue\Common\Tests\Functional\Job;

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
use Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestJob;
use Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Functional tests for the JobManager
 */
class JobManagerTest extends FunctionalTestCase
{

    /**
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @var QueueManager|MockObject
     */
    protected $mockQueueManager;

    /**
     * @var TestQueue
     */
    protected $testQueue;

    /**
     * @var array
     */
    protected $queueSettings = [];

    /**
     * @var array
     */
    protected $emittedSignals = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->mockQueueManager = $this->getMockBuilder(QueueManager::class)->disableOriginalConstructor()->getMock();
        $this->testQueue = new TestQueue('TestQueue');
        $this->mockQueueManager->expects($this->any())->method('getQueue')->with('TestQueue')->will($this->returnValue($this->testQueue));
        $this->mockQueueManager->expects($this->any())->method('getQueueSettings')->with('TestQueue')->will($this->returnCallback(function() { return $this->queueSettings; }));

        $this->jobManager = new JobManager();
        $this->inject($this->jobManager, 'queueManager', $this->mockQueueManager);

        self::$bootstrap->getSignalSlotDispatcher()->connect(JobManager::class, 'messageSubmitted', $this, 'logSignal');
        self::$bootstrap->getSignalSlotDispatcher()->connect(JobManager::class, 'messageTimeout', $this, 'logSignal');
        self::$bootstrap->getSignalSlotDispatcher()->connect(JobManager::class, 'messageReserved', $this, 'logSignal');
        self::$bootstrap->getSignalSlotDispatcher()->connect(JobManager::class, 'messageFinished', $this, 'logSignal');
        self::$bootstrap->getSignalSlotDispatcher()->connect(JobManager::class, 'messageReleased', $this, 'logSignal');
        self::$bootstrap->getSignalSlotDispatcher()->connect(JobManager::class, 'messageFailed', $this, 'logSignal');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->emittedSignals = [];
    }

    /**
     * Slot for the JobManager signals (see setUp())
     *
     * @return void
     */
    public function logSignal()
    {
        $arguments = func_get_args();
        $signalName = array_pop($arguments);
        if (!isset($this->emittedSignals[$signalName])) {
            $this->emittedSignals[$signalName] = [];
        }
        $this->emittedSignals[$signalName][] = $arguments;
    }

    /**
     * @param string $signalName
     * @param array $arguments
     */
    protected function assertSignalEmitted($signalName, array $arguments = [])
    {
        $fullSignalName = JobManager::class . '::' . $signalName;
        if (!isset($this->emittedSignals[$fullSignalName])) {
            $this->fail('Signal "' . $signalName . '" has not been emitted!');
        }
        self::assertCount(1, $this->emittedSignals[$fullSignalName]);
        foreach ($arguments as $argumentIndex => $expectedArgument) {
            $actualArgument = $this->emittedSignals[$fullSignalName][0][$argumentIndex];
            if ($expectedArgument instanceof Constraint) {
                $expectedArgument->evaluate($actualArgument);
            } else {
                self::assertSame($expectedArgument, $actualArgument);
            }
        }
    }

    /**
     * @test
     */
    public function queueEmitsMessageSubmittedSignal()
    {
        $options = ['foo' => 'bar'];
        $this->jobManager->queue('TestQueue', new TestJob(), $options);
        $this->assertSignalEmitted('messageSubmitted', [0 => $this->testQueue, 3 => $options]);
    }

    /**
     * @test
     */
    public function waitAndExecuteEmitsMessageTimeoutSignal()
    {
        $this->jobManager->queue('TestQueue', new TestJob());
        $this->jobManager->waitAndExecute('TestQueue', 0);
        $this->assertSignalEmitted('messageTimeout', [0 => $this->testQueue]);
    }

    /**
     * @test
     */
    public function waitAndExecuteEmitsMessageReservedSignal()
    {
        $this->jobManager->queue('TestQueue', new TestJob());
        $this->jobManager->waitAndExecute('TestQueue');
        $this->assertSignalEmitted('messageReserved', [0 => $this->testQueue, 1 => new IsInstanceOf(Message::class)]);
    }

    /**
     * @test
     */
    public function waitAndExecuteEmitsMessageFinishedSignal()
    {
        $this->jobManager->queue('TestQueue', new TestJob());
        $this->jobManager->waitAndExecute('TestQueue');
        $this->assertSignalEmitted('messageFinished', [0 => $this->testQueue, 1 => new IsInstanceOf(Message::class)]);
    }

    /**
     * @test
     */
    public function waitAndExecuteEmitsMessageReleasedSignal()
    {
        $releaseOptions = ['some' => 'releaseOption'];
        $this->queueSettings = ['maximumNumberOfReleases' => 1, 'releaseOptions' => $releaseOptions];
        $this->jobManager->queue('TestQueue', new TestJob(2));
        try {
            $this->jobManager->waitAndExecute('TestQueue');
        } catch (JobQueueException $exception) {
        }
        $this->assertSignalEmitted('messageReleased', [$this->testQueue, new IsInstanceOf(Message::class), $releaseOptions, new IsInstanceOf(JobQueueException::class)]);
    }

    /**
     * @test
     */
    public function waitAndExecuteEmitsMessageFailedSignal()
    {
        $this->jobManager->queue('TestQueue', new TestJob(JobManager::DEFAULT_MAXIMUM_NUMBER_RELEASES + 1));
        for ($i = 0; $i <= JobManager::DEFAULT_MAXIMUM_NUMBER_RELEASES; $i ++) {
            try {
                $this->jobManager->waitAndExecute('TestQueue');
            } catch (JobQueueException $exception) {
            }
        }
        $this->assertSignalEmitted('messageFailed', [$this->testQueue, new IsInstanceOf(Message::class)]);
    }
}
