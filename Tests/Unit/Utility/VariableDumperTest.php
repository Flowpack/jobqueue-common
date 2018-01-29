<?php
namespace Flowpack\JobQueue\Common\Tests\Unit\Utility;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\JobQueue\Common\Utility\VariableDumper;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Unit tests for the VariableDumper
 */
class VariableDumperTest extends UnitTestCase
{

    /**
     * @test
     * @dataProvider dumpValueExamples
     */
    public function dumpValueReturnsStringifiedValue($input, $expectedOutput)
    {
        $output = VariableDumper::dumpValue($input);
        $this->assertSame($expectedOutput, $output);
    }

    public function dumpValueExamples()
    {
        return [
            ['This is a longer string', 'This is a longer st…'],
            ['This is a short one', 'This is a short one'],
            [true, 'true'],
            [false, 'false'],
            [null, 'null'],
            [42, '42'],
            [3.14159265359, '3.14'],
            [['foo', 'bar', 'baz'], '[array]'],
            [new Result(), '[Neos\Error\Messages\Result]'],
            [new Error('A somewhat longer test message'), 'A somewhat longer t…'],
        ];
    }
}
