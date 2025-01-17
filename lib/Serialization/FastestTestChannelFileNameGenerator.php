<?php

declare(strict_types=1);

namespace Lendable\Clock\Serialization;

use Liuggio\Fastest\Process\EnvCommandCreator;

/**
 * File name generator that bases the file name upon the current "test channel" to
 * prevent conflicts when running a test suite that divides and conquers test cases
 * into parallel execution streams via liuggio/fastest.
 */
final class FastestTestChannelFileNameGenerator implements FileNameGenerator
{
    private string $fileName;

    public function __construct()
    {
        if (!\class_exists(EnvCommandCreator::class)) {
            throw new \RuntimeException('You must have liuggio/fastest installed to use this class.');
        }

        $envChannel = \getenv(EnvCommandCreator::ENV_TEST_CHANNEL_READABLE);
        $this->fileName = \sprintf('now_%s.json', $envChannel !== false ? $envChannel : '1');
    }

    public function generate(): string
    {
        return $this->fileName;
    }
}
