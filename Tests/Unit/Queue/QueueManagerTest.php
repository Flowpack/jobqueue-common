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

use Neos\Flow\Tests\UnitTestCase;
use Flowpack\JobQueue\Common\Queue\QueueManager;
use Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue;

/**
 * Queue manager tests
 */
class QueueManagerTest extends UnitTestCase
{
    /**
     * @var QueueManager
     */
    protected $queueManager;

    public function setUp(): void
    {
        $this->queueManager = new QueueManager();
        $this->inject($this->queueManager, 'settings', [
            'queues' => [
                'TestQueue' => [
                    'className' => TestQueue::class
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function getQueueSettingsMergesPresetWithQueueSettings()
    {
        $this->inject($this->queueManager, 'settings', [
            'presets' => [
                'somePreset' => [
                    'className' => 'Some\Preset\ClassName',
                    'maximumNumberOfReleases' => 123,
                    'queueNamePrefix' => 'presetPrefix',
                    'options' => [
                        'option1' => 'from preset',
                        'option2' => 'from preset',
                    ],
                    'releaseOptions' => [
                        'bar' => 'from preset',
                    ]
                ]
            ],
            'queues' => [
                'TestQueue' => [
                    'preset' => 'somePreset',
                    'className' => TestQueue::class,
                    'maximumNumberOfReleases' => 321,
                    'queueNamePrefix' => 'queuePrefix',
                    'options' => [
                        'option2' => 'overridden from queue',
                        'option3' => 'from queue',
                    ],
                    'releaseOptions' => [
                        'bar' => 'from queue',
                    ]
                ]
            ]
        ]);

        $expectedSettings = [
            'className' => TestQueue::class,
            'maximumNumberOfReleases' => 321,
            'queueNamePrefix' => 'queuePrefix',
            'options' => [
                'option1' => 'from preset',
                'option2' => 'overridden from queue',
                'option3' => 'from queue',
            ],
            'releaseOptions' => [
                'bar' => 'from queue',
            ],
            'preset' => 'somePreset'
        ];

        $queueSettings = $this->queueManager->getQueueSettings('TestQueue');
        self::assertSame($expectedSettings, $queueSettings);
    }

    /**
     * @test
     */
    public function getQueueCreatesInstanceByQueueName()
    {
        /** @var TestQueue $queue */
        $queue = $this->queueManager->getQueue('TestQueue');
        self::assertInstanceOf(TestQueue::class, $queue);
        self::assertSame('TestQueue', $queue->getName());
    }

    /**
     * @test
     */
    public function getQueueSetsOptionsOnInstance()
    {
        $this->inject($this->queueManager, 'settings', [
            'queues' => [
                'TestQueue' => [
                    'className' => TestQueue::class,
                    'options' => [
                        'foo' => 'bar'
                    ]
                ]
            ]
        ]);

        /** @var TestQueue $queue */
        $queue = $this->queueManager->getQueue('TestQueue');
        self::assertEquals(['foo' => 'bar'], $queue->getOptions());
    }

    /**
     * @test
     */
    public function getQueueReusesInstances()
    {
        $queue = $this->queueManager->getQueue('TestQueue');
        self::assertSame($queue, $this->queueManager->getQueue('TestQueue'));
    }

    /**
     * @test
     */
    public function getQueueThrowsExceptionWhenSettingsReferToNonExistingPreset()
    {
        self::expectException(\Flowpack\JobQueue\Common\Exception::class);
        $this->inject($this->queueManager, 'settings', [
            'queues' => [
                'TestQueue' => [
                    'className' => TestQueue::class,
                    'preset' => 'NonExistingPreset'
                ]
            ]
        ]);
        $this->queueManager->getQueue('TestQueue');
    }


    /**
     * @test
     */
    public function queueNamesArePrefixedWithDefaultQueueNamePrefix()
    {
        $this->inject($this->queueManager, 'settings', [
            'queues' => [
                'TestQueue' => [
                    'className' => TestQueue::class,
                    'queueNamePrefix' => 'specialQueue',
                ]
            ]
        ]);

        /** @var TestQueue $queue */
        $queue = $this->queueManager->getQueue('TestQueue');
        self::assertSame('specialQueueTestQueue', $queue->getName());
    }

    /**
     * @test
     */
    public function queueNamePrefixFromPresetCanBeOverruled()
    {
        $this->inject($this->queueManager, 'settings', [
            'presets' => [
                'somePreset' => [
                    'queueNamePrefix' => 'presetPrefix',
                ]
            ],
            'queues' => [
                'TestQueue' => [
                    'preset' => 'somePreset',
                    'queueNamePrefix' => 'overriddenPrefix',
                    'className' => TestQueue::class,
                ]
            ]
        ]);

        /** @var TestQueue $queue */
        $queue = $this->queueManager->getQueue('TestQueue');
        self::assertSame('overriddenPrefixTestQueue', $queue->getName());
    }
}
