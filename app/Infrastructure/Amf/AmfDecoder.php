<?php

namespace App\Infrastructure\Amf;

use stdClass;

final class AmfDecoder
{
    private string $input = '';

    private int $offset = 0;

    private int $bodyEncoding = 0;

    private int $depth = 0;

    private int $maxDepth = 64;

    private int $maxEntries = 10_000;

    private int $maxStringBytes = 262_144;

    /** @var list<string> */
    private array $strings = [];

    /** @var list<mixed> */
    private array $objects = [];

    /** @var list<array{type:string,members:list<string>,externalizable:bool,dynamic:bool}> */
    private array $traits = [];

    /** @var list<mixed> */
    private array $amf0Objects = [];

    public function decode(string $input): AmfEnvelope
    {
        $maxPayloadBytes = $this->setting('max_payload_bytes', 1_048_576);
        if (strlen($input) > $maxPayloadBytes) {
            throw new AmfException('AMF payload exceeds the configured size limit.');
        }

        $this->input = $input;
        $this->offset = 0;
        $this->bodyEncoding = 0;
        $this->depth = 0;
        $this->maxDepth = max(1, $this->setting('max_depth', 64));
        $this->maxEntries = max(1, $this->setting('max_collection_entries', 10_000));
        $this->maxStringBytes = max(1, $this->setting('max_string_bytes', 262_144));

        $version = $this->readUnsignedShort();
        if (! in_array($version, [0, 3], true)) {
            throw new AmfException("Unsupported AMF envelope version: {$version}");
        }

        $headerCount = $this->readUnsignedShort();
        if ($headerCount > max(0, $this->setting('max_headers', 16))) {
            throw new AmfException('AMF envelope contains too many headers.');
        }
        for ($index = 0; $index < $headerCount; $index++) {
            $this->resetReferences();
            $this->readUtf();
            $this->readByte();
            $this->readUnsignedLong();
            $this->readAmf0Value($this->readByte());
        }

        $messages = [];
        $messageCount = $this->readUnsignedShort();
        if ($messageCount > max(1, $this->setting('max_messages', 32))) {
            throw new AmfException('AMF envelope contains too many messages.');
        }
        for ($index = 0; $index < $messageCount; $index++) {
            $this->resetReferences();
            $target = $this->readUtf();
            $response = $this->readUtf();
            $this->readUnsignedLong();
            $data = $this->readAmf0Value($this->readByte());
            $messages[] = new AmfMessage($target, $response, $data);
        }

        if ($this->offset !== strlen($this->input)) {
            throw new AmfException("Unexpected trailing AMF data at byte {$this->offset}");
        }

        return new AmfEnvelope($version === 3 || $this->bodyEncoding === 3 ? 3 : 0, $messages);
    }

    private function resetReferences(): void
    {
        $this->strings = [];
        $this->objects = [];
        $this->traits = [];
        $this->amf0Objects = [];
    }

    private function readAmf0Value(int $marker): mixed
    {
        return $this->withinDepth(fn (): mixed => match ($marker) {
            0x00 => $this->readDouble(),
            0x01 => $this->readByte() !== 0,
            0x02 => $this->readUtf(),
            0x03 => $this->readAmf0Object(),
            0x05 => null,
            0x06 => UndefinedValue::instance(),
            0x07 => $this->readAmf0Reference(),
            0x08 => $this->readAmf0MixedArray(),
            0x0A => $this->readAmf0Array(),
            0x0B => $this->readAmf0Date(),
            0x0C => $this->readLongUtf(),
            0x0F => $this->readLongUtf(),
            0x10 => $this->readAmf0TypedObject(),
            0x11 => $this->readEmbeddedAmf3Value(),
            default => throw new AmfException(sprintf('Unsupported AMF0 marker 0x%02X at byte %d', $marker, $this->offset - 1)),
        });
    }

    private function readEmbeddedAmf3Value(): mixed
    {
        $this->bodyEncoding = 3;

        return $this->readAmf3Value();
    }

    private function readAmf0Object(): stdClass
    {
        $object = new stdClass;
        $this->amf0Objects[] = $object;

        foreach ($this->readAmf0Properties() as $name => $value) {
            $object->{$name} = $value;
        }

        return $object;
    }

    /** @return array<string, mixed> */
    private function readAmf0Properties(): array
    {
        $properties = [];

        while (true) {
            $name = $this->readUtf();
            $marker = $this->readByte();
            if ($name === '' && $marker === 0x09) {
                break;
            }
            $this->guardNextEntry(count($properties));
            $properties[$name] = $this->readAmf0Value($marker);
        }

        return $properties;
    }

    private function readAmf0Reference(): mixed
    {
        $index = $this->readUnsignedShort();

        return $this->amf0Objects[$index] ?? throw new AmfException("Invalid AMF0 reference: {$index}");
    }

    /** @return array<int|string, mixed> */
    private function readAmf0MixedArray(): array
    {
        $this->readUnsignedLong();
        $result = [];
        $this->amf0Objects[] = &$result;

        while (true) {
            $key = $this->readUtf();
            $marker = $this->readByte();
            if ($key === '' && $marker === 0x09) {
                break;
            }
            $this->guardNextEntry(count($result));
            $result[ctype_digit($key) ? (int) $key : $key] = $this->readAmf0Value($marker);
        }

        return $result;
    }

    /** @return list<mixed> */
    private function readAmf0Array(): array
    {
        $length = $this->readUnsignedLong();
        $this->guardEntryCount($length);
        $result = [];
        $this->amf0Objects[] = &$result;

        for ($index = 0; $index < $length; $index++) {
            $result[] = $this->readAmf0Value($this->readByte());
        }

        return $result;
    }

    private function readAmf0Date(): float
    {
        $milliseconds = $this->readDouble();
        $this->readUnsignedShort();

        return $milliseconds;
    }

    private function readAmf0TypedObject(): TypedObject
    {
        $object = new TypedObject(str_replace('..', '', $this->readUtf()));
        $this->amf0Objects[] = $object;

        foreach ($this->readAmf0Properties() as $name => $value) {
            $object->set($name, $value);
        }

        return $object;
    }

    private function readAmf3Value(): mixed
    {
        return $this->withinDepth(function (): mixed {
            $marker = $this->readByte();

            return match ($marker) {
                0x00 => UndefinedValue::instance(),
                0x01 => null,
                0x02 => false,
                0x03 => true,
                0x04 => $this->readAmf3Integer(),
                0x05 => $this->readDouble(),
                0x06 => $this->readAmf3String(),
                0x07, 0x0B, 0x0C => $this->readAmf3Blob(),
                0x08 => $this->readAmf3Date(),
                0x09 => $this->readAmf3Array(),
                0x0A => $this->readAmf3Object(),
                0x0D, 0x0E, 0x0F, 0x10 => $this->readAmf3Vector($marker),
                default => throw new AmfException(sprintf('Unsupported AMF3 marker 0x%02X at byte %d', $marker, $this->offset - 1)),
            };
        });
    }

    private function readAmf3Integer(): int
    {
        $value = $this->readU29();

        return ($value & 0x10000000) !== 0 ? $value | ~0x1FFFFFFF : $value;
    }

    private function readAmf3String(): string
    {
        $handle = $this->readU29();
        if (($handle & 1) === 0) {
            $index = $handle >> 1;

            return $this->strings[$index] ?? throw new AmfException("Invalid AMF3 string reference: {$index}");
        }

        $length = $handle >> 1;
        $this->guardStringLength($length);
        if ($length === 0) {
            return '';
        }

        $value = $this->readBytes($length);
        $this->strings[] = $value;

        return $value;
    }

    private function readAmf3Blob(): string
    {
        $handle = $this->readU29();
        if (($handle & 1) === 0) {
            $index = $handle >> 1;

            return $this->objects[$index] ?? throw new AmfException("Invalid AMF3 object reference: {$index}");
        }

        $length = $handle >> 1;
        $this->guardStringLength($length);
        $value = $this->readBytes($length);
        $this->objects[] = $value;

        return $value;
    }

    private function readAmf3Date(): float
    {
        $handle = $this->readU29();
        if (($handle & 1) === 0) {
            $index = $handle >> 1;

            return $this->objects[$index] ?? throw new AmfException("Invalid AMF3 date reference: {$index}");
        }

        $value = $this->readDouble();
        $this->objects[] = $value;

        return $value;
    }

    /** @return array<int|string, mixed> */
    private function readAmf3Array(): array
    {
        $handle = $this->readU29();
        if (($handle & 1) === 0) {
            $index = $handle >> 1;

            return $this->objects[$index] ?? throw new AmfException("Invalid AMF3 array reference: {$index}");
        }

        $denseLength = $handle >> 1;
        $this->guardEntryCount($denseLength);
        $result = [];
        $this->objects[] = &$result;

        while (($key = $this->readAmf3String()) !== '') {
            $this->guardNextEntry(count($result));
            $result[$key] = $this->readAmf3Value();
        }
        for ($index = 0; $index < $denseLength; $index++) {
            $result[] = $this->readAmf3Value();
        }

        return $result;
    }

    private function readAmf3Object(): mixed
    {
        $handle = $this->readU29();
        if (($handle & 1) === 0) {
            $index = $handle >> 1;

            return $this->objects[$index] ?? throw new AmfException("Invalid AMF3 object reference: {$index}");
        }

        $traitsHandle = $handle >> 1;
        if (($traitsHandle & 1) !== 0) {
            $traitsHandle >>= 1;
            $type = $this->readAmf3String();
            $externalizable = ($traitsHandle & 1) !== 0;
            $traitsHandle >>= 1;
            $dynamic = ($traitsHandle & 1) !== 0;
            $memberCount = $traitsHandle >> 1;
            $this->guardEntryCount($memberCount);
            $members = [];
            for ($index = 0; $index < $memberCount; $index++) {
                $members[] = $this->readAmf3String();
            }
            $traits = compact('type', 'members', 'externalizable', 'dynamic');
            $this->traits[] = $traits;
        } else {
            $index = $traitsHandle >> 1;
            $traits = $this->traits[$index] ?? throw new AmfException("Invalid AMF3 traits reference: {$index}");
        }

        if ($traits['externalizable'] && in_array($traits['type'], ['flex.messaging.io.ArrayCollection', 'flex.messaging.io.ObjectProxy'], true)) {
            $referenceIndex = count($this->objects);
            $this->objects[] = null;
            $value = $this->readAmf3Value();
            $this->objects[$referenceIndex] = $value;

            return $value;
        }

        $object = $traits['type'] === '' ? new stdClass : new TypedObject($traits['type']);
        $this->objects[] = $object;

        if ($traits['externalizable']) {
            $value = $this->readAmf3Value();
            if ($object instanceof TypedObject) {
                $object->set('_externalizedData', $value);
            } else {
                $object->_externalizedData = $value;
            }

            return $object;
        }

        foreach ($traits['members'] as $member) {
            $value = $this->readAmf3Value();
            $object instanceof TypedObject ? $object->set($member, $value) : $object->{$member} = $value;
        }
        if ($traits['dynamic']) {
            $dynamicMembers = 0;
            while (($member = $this->readAmf3String()) !== '') {
                $this->guardNextEntry($dynamicMembers++);
                $value = $this->readAmf3Value();
                $object instanceof TypedObject ? $object->set($member, $value) : $object->{$member} = $value;
            }
        }

        return $object;
    }

    /** @return list<int|float|mixed> */
    private function readAmf3Vector(int $marker): array
    {
        $handle = $this->readU29();
        if (($handle & 1) === 0) {
            $index = $handle >> 1;

            return $this->objects[$index] ?? throw new AmfException("Invalid AMF3 vector reference: {$index}");
        }

        $length = $handle >> 1;
        $this->guardEntryCount($length);
        $this->readByte();
        if ($marker === 0x10) {
            $this->readAmf3String();
        }

        $result = [];
        $this->objects[] = &$result;
        for ($index = 0; $index < $length; $index++) {
            $result[] = match ($marker) {
                0x0D => $this->readSignedLong(),
                0x0E => unpack('N', $this->readBytes(4))[1],
                0x0F => $this->readDouble(),
                default => $this->readAmf3Value(),
            };
        }

        return $result;
    }

    private function readU29(): int
    {
        $value = 0;
        for ($index = 0; $index < 3; $index++) {
            $byte = $this->readByte();
            if (($byte & 0x80) === 0) {
                return ($value << 7) | $byte;
            }
            $value = ($value << 7) | ($byte & 0x7F);
        }

        return ($value << 8) | $this->readByte();
    }

    private function readDouble(): float
    {
        return unpack('E', $this->readBytes(8))[1];
    }

    private function readUtf(): string
    {
        $length = $this->readUnsignedShort();
        $this->guardStringLength($length);

        return $this->readBytes($length);
    }

    private function readLongUtf(): string
    {
        $length = $this->readUnsignedLong();
        $this->guardStringLength($length);

        return $this->readBytes($length);
    }

    private function readUnsignedShort(): int
    {
        return unpack('n', $this->readBytes(2))[1];
    }

    private function readUnsignedLong(): int
    {
        return unpack('N', $this->readBytes(4))[1];
    }

    private function readSignedLong(): int
    {
        $value = $this->readUnsignedLong();

        return $value >= 0x80000000 ? $value - 0x100000000 : $value;
    }

    private function readByte(): int
    {
        return ord($this->readBytes(1));
    }

    private function readBytes(int $length): string
    {
        if ($length < 0 || $this->offset + $length > strlen($this->input)) {
            throw new AmfException("Unexpected end of AMF payload at byte {$this->offset}");
        }

        $value = substr($this->input, $this->offset, $length);
        $this->offset += $length;

        return $value;
    }

    private function guardEntryCount(int $count): void
    {
        if ($count > $this->maxEntries) {
            throw new AmfException('AMF collection exceeds the configured entry limit.');
        }
    }

    private function guardNextEntry(int $currentCount): void
    {
        if ($currentCount >= $this->maxEntries) {
            throw new AmfException('AMF collection exceeds the configured entry limit.');
        }
    }

    private function guardStringLength(int $length): void
    {
        if ($length > $this->maxStringBytes) {
            throw new AmfException('AMF string exceeds the configured size limit.');
        }
    }

    private function withinDepth(callable $reader): mixed
    {
        $this->depth++;
        if ($this->depth > $this->maxDepth) {
            $this->depth--;

            throw new AmfException('AMF value exceeds the configured nesting limit.');
        }

        try {
            return $reader();
        } finally {
            $this->depth--;
        }
    }

    private function setting(string $name, int $default): int
    {
        if (! app()->bound('config')) {
            return $default;
        }

        return (int) config("panfu.amf.{$name}", $default);
    }
}
