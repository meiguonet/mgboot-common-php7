<?php

namespace mgboot\annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Scheduled
{
    /**
     * @var string
     */
    private $cronExpression;

    public function __construct($arg0)
    {
        if (is_string($arg0) && $arg0 !== '') {
            $this->cronExpression = $arg0;
            return;
        }

        if (is_array($arg0) && is_string($arg0['value'])) {
            $this->cronExpression = $arg0['value'];
            return;
        }

        $this->cronExpression = '';
    }

    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }
}
