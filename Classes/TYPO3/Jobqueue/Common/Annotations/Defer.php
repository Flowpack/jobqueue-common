<?php
namespace TYPO3\Jobqueue\Common\Annotations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Jobqueue.Common". *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Annotations\Annotation as DoctrineAnnotation;

/**
 * @Annotation
 * @DoctrineAnnotation\Target("METHOD")
 */
final class Defer {

	/**
	 * The queue name to queue jobs
	 * @var string
	 */
	public $queueName;

	/**
	 * @param array $values
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $values) {
		if (!isset($values['value']) && !isset($values['queueName'])) {
			throw new \InvalidArgumentException('A Defer annotation must specify a queueName.', 1334128835);
		}
		$this->queueName = isset($values['queueName']) ? $values['queueName'] : $values['value'];
	}

}