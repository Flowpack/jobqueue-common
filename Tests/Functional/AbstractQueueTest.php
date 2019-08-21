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
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\TypeHandling;

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
    public function setUp(): void
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
        self::assertInternalType('string', $messageId);
    }

    /**
     * @test
     */
    public function submitAndWaitWithMessageWorks()
    {
        $payload = 'Yeah, tell someone it works!';
        $this->queue->submit($payload);

        $message = $this->queue->waitAndTake(1);
        self::assertInstanceOf(Message::class, $message, 'waitAndTake should return message');
        self::assertEquals($payload, $message->getPayload(), 'message should have payload as before');
    }

    /**
     * @test
     */
    public function waitForMessageTimesOut()
    {
        self::assertNull($this->queue->waitAndTake(1), 'wait should return NULL after timeout');
    }

    /**
     * @test
     */
    public function peekReturnsNextMessagesIfQueueHasMessages()
    {
        $this->queue->submit('First message');
        $this->queue->submit('Another message');

        $messages = $this->queue->peek(1);
        self::assertCount(1, $messages, 'peek should return a message');
        /** @var Message $firstMessage */
        $firstMessage = array_shift($messages);
        self::assertEquals('First message', $firstMessage->getPayload());

        $messages = $this->queue->peek(1);
        self::assertCount(1, $messages, 'peek should return a message again');
        /** @var Message $firstMessage */
        $firstMessage = array_shift($messages);
        self::assertEquals('First message', $firstMessage->getPayload(), 'second peek should return the same message again');
    }

    /**
     * @test
     */
    public function peekReturnsEmptyArrayIfQueueHasNoMessage()
    {
        self::assertEquals([], $this->queue->peek(), 'peek should not return a message');
    }

    /**
     * @test
     */
    public function waitAndReserveWithFinishRemovesMessage()
    {
        $payload = 'A message';
        $messageId = $this->queue->submit($payload);

        $message = $this->queue->waitAndReserve(1);
        self::assertNotNull($message, 'waitAndReserve should receive message');
        self::assertSame($payload, $message->getPayload(), 'message should have payload as before');

        $message = $this->queue->peek();
        self::assertEquals([], $message, 'no message should be present in queue');

        self::assertTrue($this->queue->finish($messageId));
    }

    /**
     * @test
     */
    public function releasePutsMessageBackToQueue()
    {
        $messageId = $this->queue->submit('A message');

        $this->queue->waitAndReserve(1);
        self::assertSame(0, $this->queue->countReady());

        $this->queue->release($messageId);
        self::assertSame(1, $this->queue->countReady());
    }

    /**
     * @test
     */
    public function releaseIncreasesNumberOfReleases()
    {
        $messageId = $this->queue->submit('A message');

        $message = $this->queue->waitAndReserve(1);
        self::assertSame(0, $message->getNumberOfReleases());

        $this->queue->release($messageId);
        $message = $this->queue->waitAndReserve(1);
        self::assertSame(1, $message->getNumberOfReleases());

        $this->queue->release($messageId);
        $message = $this->queue->waitAndReserve(1);
        self::assertSame(2, $message->getNumberOfReleases());

        $this->queue->abort($messageId);
    }

    /**
     * @test
     */
    public function abortRemovesMessageFromActiveQueue()
    {
        $messageId = $this->queue->submit('A message');

        $this->queue->waitAndReserve(1);

        $this->queue->abort($messageId);
        self::assertSame(0, $this->queue->countReady());
        self::assertNull($this->queue->waitAndTake(1));
    }

    /**
     * @test
     */
    public function countReadyReturnsZeroByDefault()
    {
        self::assertSame(0, $this->queue->countReady());
    }

    /**
     * @test
     */
    public function countReadyReturnsNumberOfReadyJobs()
    {
        $this->queue->submit('First message');
        $this->queue->submit('Second message');

        self::assertSame(2, $this->queue->countReady());
    }

    /**
     * @test
     */
    public function countFailedReturnsZeroByDefault()
    {
        self::assertSame(0, $this->queue->countFailed());
    }

    /**
     * @test
     */
    public function countFailedReturnsNumberOfFailedMessages()
    {
        $messageId = $this->queue->submit('A message');

        $this->queue->waitAndReserve(1);
        self::assertSame(0, $this->queue->countFailed());

        $this->queue->abort($messageId);
        self::assertSame(1, $this->queue->countFailed());
    }

    /**
     * @test
     */
    public function countReservedReturnsZeroByDefault()
    {
        self::assertSame(0, $this->queue->countReserved());
    }

    /**
     * @test
     */
    public function countReservedReturnsNumberOfReservedMessages()
    {
        $messageId = $this->queue->submit('A message');

        $this->queue->waitAndReserve(1);
        self::assertSame(1, $this->queue->countReserved());

        $this->queue->abort($messageId);
        self::assertSame(0, $this->queue->countReserved());
    }
}
