<?php

namespace mgboot\annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class WsTimer
{
    /**
     * @var string
     */
    private $value;

    public function __construct($arg0)
    {
        if (is_string($arg0) && $arg0 !== '') {
            $this->value = $arg0;
            return;
        }

        if (is_array($arg0) && is_string($arg0['value'])) {
            $this->value = $arg0['value'];
            return;
        }

        $this->value = '';
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
