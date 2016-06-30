<?php
namespace Flowpack\JobQueue\Common\Tests\Functional;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Utility\TypeHandling;

abstract class AbstractQueueTest extends FunctionalTestCase
{

    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * @var array
     */
    protected $queueSettings;

    /**
     * Set up dependencies
     */
    public function setUp()
    {
        parent::setUp();
        $configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $packageKey = $this->objectManager->getPackageKeyByObjectName(TypeHandling::getTypeForValue($this));
        $packageSettings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packageKey);
        if (!isset($packageSettings['testing']['enabled']) || $packageSettings['testing']['enabled'] !== true) {
            $this->markTestSkipped(sprintf('Queue is not configured (%s.testing.enabled != TRUE)', $packageKey));
        }
        $this->queueSettings = $packageSettings['testing'];
        $this->queue = $this->getQueue();
        $this->queue->setUp();
        $this->queue->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->queue->flush();
    }

    /**
     * @return QueueInterface
     */
    abstract protected function getQueue();

    /**
     * @test
     */
    public function submitReturnsMessageId()
    {
        $messageId = $this->queue->submit('some message payload');
        $this->assertInternalType('string', $messageId);
    }

    /**
     * @test
     */
    public function submitAndWaitWithMessageWorks()
    {
        $payload = 'Yeah, tell someone it works!';
        $this->queue->submit($payload);

        $message = $this->queue->waitAndTake(1);
        $this->assertInstanceOf(Message::class, $message, 'waitAndTake should return message');
        $this->assertEquals($payload, $message->getPayload(), 'message should have payload as before');
    }

    /**
     * @test
     */
    public function waitForMessageTimesOut()
    {
        $this->assertNull($this->queue->waitAndTake(1), 'wait should return NULL after timeout');
    }

    /**
     * @test
     */
    public function peekReturnsNextMessagesIfQueueHasMessages()
    {
        $this->queue->submit('First message');
        $this->queue->submit('Another message');

        $messages = $this->queue->peek(1);
        $this->assertCount(1, $messages, 'peek should return a message');
        /** @var Message $firstMessage */
        $firstMessage = array_shift($messages);
        $this->assertEquals('First message', $firstMessage->getPayload());

        $messages = $this->queue->peek(1);
        $this->assertCount(1, $messages, 'peek should return a message again');
        /** @var Message $firstMessage */
        $firstMessage = array_shift($messages);
        $this->assertEquals('First message', $firstMessage->getPayload(), 'second peek should return the same message again');
    }

    /**
     * @test
     */
    public function peekReturnsEmptyArrayIfQueueHasNoMessage()
    {
        $this->assertEquals([], $this->queue->peek(), 'peek should not return a message');
    }

    /**
     * @test
     */
    public function waitAndReserveWithFinishRemovesMessage()
    {
        $payload = 'A message';
        $messageId = $this->queue->submit($payload);

        $message = $this->queue->waitAndReserve(1);
        $this->assertNotNull($message, 'waitAndReserve should receive message');
        $this->assertSame($payload, $message->getPayload(), 'message should have payload as before');

        $message = $this->queue->peek();
        $this->assertEquals([], $message, 'no message should be present in queue');

        $this->assertTrue($this->queue->finish($messageId));
    }

    /**
     * @test
     */
    public function releasePutsMessageBackToQueue()
    {
        $messageId = $this->queue->submit('A message');

        $this->queue->waitAndReserve(1);
        $this->assertSame(0, $this->queue->count());

        $this->queue->release($messageId);
        $this->assertSame(1, $this->queue->count());
    }

    /**
     * @test
     */
    public function releaseIncreasesNumberOfFailures()
    {
        $messageId = $this->queue->submit('A message');

        $message = $this->queue->waitAndReserve(1);
        $this->assertSame(0, $message->getNumberOfFailures());

        $this->queue->release($messageId);
        $message = $this->queue->waitAndReserve(1);
        $this->assertSame(1, $message->getNumberOfFailures());

        $this->queue->release($messageId);
        $message = $this->queue->waitAndReserve(1);
        $this->assertSame(2, $message->getNumberOfFailures());
    }

    /**
     * @test
     */
    public function abortRemovesMessageFromActiveQueue()
    {
        $messageId = $this->queue->submit('A message');

        $this->queue->waitAndReserve(1);

        $this->queue->abort($messageId);
        $this->assertSame(0, $this->queue->count());
        $this->assertNull($this->queue->waitAndTake(1));
    }

    /**
     * @test
     */
    public function countReturnsZeroByDefault()
    {
        $this->assertSame(0, $this->queue->count());
    }

    /**
     * @test
     */
    public function countReturnsNumberOfReadyJobs()
    {
        $this->queue->submit('First message');
        $this->queue->submit('Second message');

        $this->assertSame(2, $this->queue->count());
    }
}