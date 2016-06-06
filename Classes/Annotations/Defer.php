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

use Doctrine\Common\Annotations\Annotation as DoctrineAnnotation;

/**
 * @Annotation
 * @DoctrineAnnotation\Target("METHOD")
 */
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
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (!isset($values['value']) && !isset($values['queueName'])) {
            throw new \InvalidArgumentException('A Defer annotation must specify a queueName.', 1334128835);
        }
        $this->queueName = isset($values['queueName']) ? $values['queueName'] : $values['value'];
        $this->options = isset($values['options']) ? $values['options'] : [];
    }
}
