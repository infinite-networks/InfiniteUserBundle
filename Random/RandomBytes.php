<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
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