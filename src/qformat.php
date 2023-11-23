<?php

namespace Artbycode\QueryTable;

use Carbon\Carbon;


class QFormat
{
    const DATE = 'date';
    const DATETIME = 'datetime';
    const TIME = 'time';

    const INTEGER = 'integer';
    const FLOAT = 'float';

    const BOOLEAN = 'boolean';

    const TEXT = 'text';
    const UPPERCASE = 'uppercase';
    const LOWERCASE = 'lowercase';
    const TITLECASE = 'titlecase';
    const UCFIRST = 'ucfirst';

    private mixed $formated;

    public function __construct($value, string $format)
    {
        $this->formated = $this->format($value, $format);
    }

    public static function make($value, string $format): self
    {
        return new self($value, $format);
    }

    public function getFormated(): mixed
    {
        return $this->formated;
    }

    public  function format($value, string $format): mixed
    {
        switch ($format) {
            case self::DATE:
                return $this->formatDate($value);
            case self::DATETIME:
                return $this->formatDateTime($value);
            case self::TIME:
                return $this->formatTime($value);
            case self::INTEGER:
                return $this->formatInteger($value);
            case self::BOOLEAN:
                return $this->formatBoolean($value);
            case self::TEXT:
                return $this->formatText($value);
            case self::UPPERCASE:
                return $this->formatUppercase($value);
            case self::LOWERCASE:
                return $this->formatLowercase($value);
            case self::TITLECASE:
                return $this->formatTitlecase($value);
            case self::UCFIRST:
                return $this->formatUcfirst($value);
            case self::FLOAT:
                return $this->formatFloat($value);
            default:
                return $value;
        }
    }

    public function formatDate($value): string
    {
        // with carbon format
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function formatDateTime($value): string
    {
        // with carbon format
        return Carbon::parse($value)->format('d/m/Y H:i:s');
    }


    public function formatTime($value): string
    {
        // with carbon format
        return Carbon::parse($value)->format('H:i:s');
    }

    public function formatInteger($value): int
    {
        return intval($value);
    }

    public function formatBoolean($value): bool
    {
        return $value ? true : false;
    }

    public function formatText($value): string
    {
        return $value;
    }

    public function formatUppercase($value): string
    {
        return strtoupper($value);
    }

    public function formatLowercase($value): string
    {
        return strtolower($value);
    }

    public function formatTitlecase($value): string
    {
        return ucwords($value);
    }

    public function formatUcfirst($value): string
    {
        return ucfirst($value);
    }

    public function formatFloat($value): float
    {
        return floatval($value);
    }

    public static function inList(string $format): bool
    {
        $listFormat =  [
            self::DATE,
            self::DATETIME,
            self::TIME,
            self::INTEGER,
            self::FLOAT,
            self::BOOLEAN,
            self::TEXT,
            self::UPPERCASE,
            self::LOWERCASE,
            self::TITLECASE,
            self::UCFIRST,
        ];
        return in_array($format, $listFormat);
    }
}
