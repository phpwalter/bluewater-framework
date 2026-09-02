<?php

/**
 * @file Summary.php
 * @path src/OpenApi/Summary.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the endpoint-method attribute used to provide OpenAPI operation summaries.
 */

declare(strict_types=1);

namespace Bluewater\OpenApi;

use Attribute;
use InvalidArgumentException;

/**
 * Supplies a stable human-readable summary for an endpoint operation.
 *
 * OpenApiGenerator emits the value verbatim and does not localize it.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Summary
{
    /**
     * Creates a non-blank operation summary.
     *
     * @param non-empty-string $value Human-readable OpenAPI summary.
     *
     * @throws InvalidArgumentException When the summary is blank.
     */
    public function __construct(public string $value)
    {
        if (trim($this->value) === '') {
            throw new InvalidArgumentException('An OpenAPI summary must not be blank.');
        }
    }
}
