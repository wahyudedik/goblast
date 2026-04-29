<?php

namespace App\Services\Contracts;

use App\Models\Template;

interface TemplateServiceInterface
{
    /**
     * Render a template with variable substitution.
     *
     * @param  array<string, mixed>  $context
     */
    public function render(Template $template, array $context): string;

    /**
     * Validate template format and length.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(string $name, string $content): void;

    /**
     * Extract variables from template content.
     *
     * @return array<int, string>
     */
    public function getVariables(string $content): array;
}
