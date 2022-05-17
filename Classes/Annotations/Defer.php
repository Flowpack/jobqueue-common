<?php
namespace Flowpack\JobQueue\Common\Annotations;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Defer
{
    /**
     * The queue name to queue jobs
     * @var string
     */
    public $queueName;

    /**
     * Optional key/value array of options passed to the queue (for example array('delay' => 123) - Supported options depend on the concrete queue implementation)
     * @var array
     */
    public $options;

    /**
     * @param string|null $queueName
     * @param array|null $options
     * @param string|null $value
     */
    public function __construct(?string $queueName = null, ?array $options = null, ?string $value = null)
    {
        if ($value === null && $queueName === null) {
            throw new \InvalidArgumentException('A Defer attribute must specify a queueName.', 1334128835);
        }
        $this->queueName = $queueName ?? $value;
        $this->options = $options ?? [];
    }
}
