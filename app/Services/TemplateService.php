<?php

namespace App\Services;

use App\Models\Template;
use App\Services\Contracts\TemplateServiceInterface;
use Psr\Log\LoggerInterface;

class TemplateService implements TemplateServiceInterface
{
    /**
     * Maximum allowed template content length (characters).
     */
    private const MAX_CONTENT_LENGTH = 4096;

    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Render a template with variable substitution.
     *
     * @param  array<string, mixed>  $context
     */
    public function render(Template $template, array $context): string
    {
        $content = $template->content;

        // Extract variables from template (format: {variable_name})
        $variables = $this->getVariables($content);

        foreach ($variables as $variable) {
            $value = $context[$variable] ?? '';

            // Log warning if variable is missing
            if (! isset($context[$variable])) {
                if ($this->logger) {
                    $this->logger->warning('Template variable missing', [
                        'template_id' => $template->id,
                        'template_name' => $template->name,
                        'variable' => $variable,
                        'context_keys' => array_keys($context),
                    ]);
                }
            }

            // Replace variable with value (empty string if not found)
            $content = str_replace("{{$variable}}", (string) $value, $content);
        }

        return $content;
    }

    /**
     * Validate template format and length.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(string $name, string $content): void
    {
        // Validate name is not empty
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Template name cannot be empty');
        }

        // Validate content is not empty
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('Template content cannot be empty');
        }

        // Validate content length does not exceed maximum
        if (strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw new \InvalidArgumentException(
                'Template content exceeds maximum length of '.self::MAX_CONTENT_LENGTH.' characters. Current length: '.strlen($content)
            );
        }
    }

    /**
     * Extract variables from template content.
     *
     * Variables are identified by the pattern {variable_name} where variable_name
     * starts with a letter or underscore and contains only alphanumeric characters and underscores.
     *
     * @return array<int, string>
     */
    public function getVariables(string $content): array
    {
        $variables = [];

        // Match all variables in format {variable_name}
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $content, $matches)) {
            // Get unique variable names (in case a variable appears multiple times)
            $variables = array_unique($matches[1]);
        }

        return array_values($variables);
    }
}
