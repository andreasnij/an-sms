<?php

namespace PHPUnit\Framework\Attributes;

if (!class_exists(AllowMockObjectsWithoutExpectations::class)) {
    #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
    class AllowMockObjectsWithoutExpectations {} // @phpcs:ignore
}
