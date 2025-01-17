<?php

declare(strict_types=1);

namespace Lendable\Clock;

use Lendable\Clock\Serialization\FileNameGenerator;

/**
 * Stores the provided current time to disk to persist between process executions.
 *
 * The only real use case for such scenario is functional testing.
 */
final class PersistedFixedClock implements MutableClock
{
    private const SERIALIZATION_FORMAT = 'Y-m-d\TH:i:s.u';

    private string $serializedStorageDirectory;

    private FileNameGenerator $fileNameGenerator;

    private FixedClock $delegate;

    private function __construct(string $serializedStorageDirectory, FileNameGenerator $fileNameGenerator)
    {
        if (!\extension_loaded('json')) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('ext-json is required to use this class.');
            // @codeCoverageIgnoreEnd
        }

        $this->serializedStorageDirectory = $serializedStorageDirectory;
        $this->fileNameGenerator = $fileNameGenerator;
    }

    public static function fromPersisted(string $serializedStorageDirectory, FileNameGenerator $fileNameGenerator): self
    {
        $instance = new self($serializedStorageDirectory, $fileNameGenerator);
        $instance->load();

        return $instance;
    }

    public static function initializeWith(string $serializedStorageDirectory, FileNameGenerator $fileNameGenerator, \DateTimeImmutable $now): self
    {
        $instance = new self($serializedStorageDirectory, $fileNameGenerator);
        $instance->delegate = new FixedClock($now);
        $instance->persist();

        return $instance;
    }

    public function now(): \DateTimeImmutable
    {
        return $this->delegate->now();
    }

    public function nowMutable(): \DateTime
    {
        return $this->delegate->nowMutable();
    }

    public function changeTimeTo(\DateTimeInterface $time): void
    {
        $this->delegate->changeTimeTo($time);
        $this->persist();
    }

    private function load(): void
    {
        $path = $this->getSerializationFilePath();
        $contents = \file_get_contents($path);
        \assert(\is_string($contents));
        $data = \json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);
        $now = \DateTimeImmutable::createFromFormat(
            self::SERIALIZATION_FORMAT,
            $data['timestamp'],
            new \DateTimeZone($data['timezone'])
        );
        \assert($now instanceof \DateTimeImmutable);

        $this->delegate = new FixedClock($now);
    }

    private function persist(): void
    {
        $now = $this->delegate->now();

        \file_put_contents(
            $this->getSerializationFilePath(),
            \json_encode(
                [
                    'timestamp' => $now->format(self::SERIALIZATION_FORMAT),
                    'timezone' => $now->getTimezone()->getName(),
                ],
                \JSON_THROW_ON_ERROR
            )
        );
    }

    private function getSerializationFilePath(): string
    {
        return $this->serializedStorageDirectory.'/'.$this->fileNameGenerator->generate();
    }
}
