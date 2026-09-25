<?php

namespace Microscrap\Bindings\MPSSE;

/**
 * What a run of MPSSE calls would have exchanged over USB: the bytes it would have written, and the
 * reads it would have made, in order. Sent as one write, its reply decodes to what the live reads returned.
 */
final class MPSSERecording
{
    public string $commands = '';

    /** @var list<array{int, bool}> each read: [length, whether it is a 1-bit I2C ACK] */
    public array $reads = [];

    public function expect(int $length, bool $ack): void
    {
        $this->reads[] = [$length, $ack];
    }

    public function responseLength(): int
    {
        return array_sum(array_map(fn (array $read): int => $read[0], $this->reads));
    }

    /** @return array{bool, string} [every ACK bit low, the data bytes in the order they were read] */
    public function decode(string $reply): array
    {
        $acked = true;
        $data = '';
        $at = 0;

        foreach ($this->reads as [$length, $ack]) {
            $chunk = substr($reply, $at, $length);
            $at += $length;

            if ($ack) {
                $acked = $acked && (ord($chunk) & 0x01) === 0;
            } else {
                $data .= $chunk;
            }
        }

        return [$acked, $data];
    }
}
