<?php declare(strict_types=1);

/**
 * This file is part of Aspen Rhino.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Random;

class RandomBytes
{
    /**
     * A wrapper for random_bytes that can be mocked for functional tests.
     */
    public function randomBytes(int $number): string
    {
        return random_bytes($number);
    }
}