<?php

namespace App\Infrastructure\Amf;

use stdClass;

final class AmfEncoder
{
    private string $output = '';

    private int $encoding = 0;

    /** @var array<string, int> */
    private array $strings = [];

    public function encode(AmfEnvelope $envelope): string
    {
        $this->output = '';
        $this->encoding = $envelope->encoding;
        $this->writeUnsignedShort(0);
        $this->writeUnsignedShort(0);
        $this->writeUnsignedShort(count($envelope->messages));

        foreach ($envelope->messages as $message) {
            $this->strings = [];
            $this->writeUtf($message->target);
            $this->writeUtf($message->response);
            $body = $this->encodeBody($message->data);
            $this->writeUnsignedLong(strlen($body));
            $this->output .= $body;
        }

        return $this->output;
    }

    private function encodeBody(mixed $value): string
    {
        $envelope = $this->output;
        $this->output = '';
        if ($this->encoding === 3) {
            $this->writeByte(0x11);
            $this->writeAmf3Value($value);
        } else {
            $this->writeAmf0Value($value);
        }
        $body = $this->output;
        $this->output = $envelope;

        return $body;
    }

    private function writeAmf0Value(mixed $value): void
    {
        if ($value instanceof UndefinedValue) {
            $this->writeByte(0x06);
        } elseif ($value === null) {
            $this->writeByte(0x05);
        } elseif (is_bool($value)) {
            $this->writeByte(0x01);
            $this->writeByte($value ? 1 : 0);
        } elseif (is_int($value) || is_float($value)) {
            $this->writeByte(0x00);
            $this->writeDouble((float) $value);
        } elseif (is_string($value)) {
            if (strlen($value) < 65536) {
                $this->writeByte(0x02);
                $this->writeUtf($value);
            } else {
                $this->writeByte(0x0C);
                $this->writeUnsignedLong(strlen($value));
                $this->output .= $value;
            }
        } elseif (is_array($value)) {
            $this->writeAmf0Array($value);
        } elseif ($value instanceof TypedObject) {
            $this->writeByte(0x10);
            $this->writeUtf($value->type);
            $this->writeAmf0Properties($value->properties());
        } elseif ($value instanceof stdClass || is_object($value)) {
            $this->writeByte(0x03);
            $this->writeAmf0Properties(get_object_vars($value));
        } else {
            throw new AmfException('Cannot encode value as AMF0: '.get_debug_type($value));
        }
    }

    /** @param array<int|string, mixed> $value */
    private function writeAmf0Array(array $value): void
    {
        if (array_is_list($value)) {
            $this->writeByte(0x0A);
            $this->writeUnsignedLong(count($value));
            foreach ($value as $entry) {
                $this->writeAmf0Value($entry);
            }

            return;
        }

        $this->writeByte(0x08);
        $this->writeUnsignedLong(count($value));
        $this->writeAmf0Properties($value);
    }

    /** @param array<int|string, mixed> $properties */
    private function writeAmf0Properties(array $properties): void
    {
        foreach ($properties as $name => $value) {
            $this->writeUtf((string) $name);
            $this->writeAmf0Value($value);
        }
        $this->writeUnsignedShort(0);
        $this->writeByte(0x09);
    }

    private function writeAmf3Value(mixed $value): void
    {
        if ($value instanceof UndefinedValue) {
            $this->writeByte(0x00);
        } elseif ($value === null) {
            $this->writeByte(0x01);
        } elseif (is_bool($value)) {
            $this->writeByte($value ? 0x03 : 0x02);
        } elseif (is_int($value) && $value >= -268435456 && $value <= 268435455) {
            $this->writeByte(0x04);
            $this->writeU29($value & 0x1FFFFFFF);
        } elseif (is_int($value) || is_float($value)) {
            $this->writeByte(0x05);
            $this->writeDouble((float) $value);
        } elseif (is_string($value)) {
            $this->writeByte(0x06);
            $this->writeAmf3String($value);
        } elseif (is_array($value)) {
            $this->writeAmf3Array($value);
        } elseif ($value instanceof TypedObject) {
            $this->writeAmf3TypedObject($value);
        } elseif ($value instanceof stdClass || is_object($value)) {
            $this->writeAmf3AnonymousObject(get_object_vars($value));
        } else {
            throw new AmfException('Cannot encode value as AMF3: '.get_debug_type($value));
        }
    }

    private function writeAmf3String(string $value): void
    {
        if ($value === '') {
            $this->writeU29(1);

            return;
        }
        if (array_key_exists($value, $this->strings)) {
            $this->writeU29($this->strings[$value] << 1);

            return;
        }

        $this->strings[$value] = count($this->strings);
        $this->writeU29((strlen($value) << 1) | 1);
        $this->output .= $value;
    }

    /** @param array<int|string, mixed> $value */
    private function writeAmf3Array(array $value): void
    {
        if (! array_is_list($value)) {
            $this->writeAmf3AnonymousObject($value);

            return;
        }

        $this->writeByte(0x09);
        $this->writeU29((count($value) << 1) | 1);
        $this->writeAmf3String('');
        foreach ($value as $entry) {
            $this->writeAmf3Value($entry);
        }
    }

    private function writeAmf3TypedObject(TypedObject $object): void
    {
        $properties = $object->properties();
        $this->writeByte(0x0A);
        $this->writeU29((count($properties) << 4) | 3);
        $this->writeAmf3String($object->type);
        foreach (array_keys($properties) as $name) {
            $this->writeAmf3String($name);
        }
        foreach ($properties as $value) {
            $this->writeAmf3Value($value);
        }
    }

    /** @param array<int|string, mixed> $properties */
    private function writeAmf3AnonymousObject(array $properties): void
    {
        $this->writeByte(0x0A);
        $this->writeU29(0x0B);
        $this->writeAmf3String('');
        foreach ($properties as $name => $value) {
            $this->writeAmf3String((string) $name);
            $this->writeAmf3Value($value);
        }
        $this->writeAmf3String('');
    }

    private function writeU29(int $value): void
    {
        if ($value < 0x80) {
            $this->writeByte($value);
        } elseif ($value < 0x4000) {
            $this->writeByte((($value >> 7) & 0x7F) | 0x80);
            $this->writeByte($value & 0x7F);
        } elseif ($value < 0x200000) {
            $this->writeByte((($value >> 14) & 0x7F) | 0x80);
            $this->writeByte((($value >> 7) & 0x7F) | 0x80);
            $this->writeByte($value & 0x7F);
        } else {
            $this->writeByte((($value >> 22) & 0x7F) | 0x80);
            $this->writeByte((($value >> 15) & 0x7F) | 0x80);
            $this->writeByte((($value >> 8) & 0x7F) | 0x80);
            $this->writeByte($value & 0xFF);
        }
    }

    private function writeDouble(float $value): void
    {
        $this->output .= pack('E', $value);
    }

    private function writeUtf(string $value): void
    {
        $this->writeUnsignedShort(strlen($value));
        $this->output .= $value;
    }

    private function writeUnsignedShort(int $value): void
    {
        $this->output .= pack('n', $value);
    }

    private function writeUnsignedLong(int $value): void
    {
        $this->output .= pack('N', $value);
    }

    private function writeByte(int $value): void
    {
        $this->output .= chr($value & 0xFF);
    }
}
