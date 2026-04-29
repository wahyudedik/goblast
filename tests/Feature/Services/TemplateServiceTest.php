<?php

namespace Tests\Feature\Services;

use App\Models\Template;
use App\Services\TemplateService;
use Tests\TestCase;

class TemplateServiceTest extends TestCase
{
    private TemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Get the service from the container which will have the logger injected
        $this->service = app(TemplateService::class);
    }

    // ============ getVariables Tests ============

    public function test_get_variables_extracts_single_variable(): void
    {
        $content = 'Hello {name}';
        $variables = $this->service->getVariables($content);

        $this->assertCount(1, $variables);
        $this->assertContains('name', $variables);
    }

    public function test_get_variables_extracts_multiple_variables(): void
    {
        $content = 'Hello {name}, your status is {status} and balance is {balance}';
        $variables = $this->service->getVariables($content);

        $this->assertCount(3, $variables);
        $this->assertContains('name', $variables);
        $this->assertContains('status', $variables);
        $this->assertContains('balance', $variables);
    }

    public function test_get_variables_returns_unique_variables(): void
    {
        $content = 'Hello {name}, welcome {name}. Your name is {name}.';
        $variables = $this->service->getVariables($content);

        $this->assertCount(1, $variables);
        $this->assertContains('name', $variables);
    }

    public function test_get_variables_handles_underscores_and_numbers(): void
    {
        $content = 'Variables: {var_1}, {_private}, {test123}';
        $variables = $this->service->getVariables($content);

        $this->assertCount(3, $variables);
        $this->assertContains('var_1', $variables);
        $this->assertContains('_private', $variables);
        $this->assertContains('test123', $variables);
    }

    public function test_get_variables_ignores_invalid_variable_names(): void
    {
        $content = 'Valid: {name}, Invalid: {123invalid}, {-dash}, {space name}';
        $variables = $this->service->getVariables($content);

        $this->assertCount(1, $variables);
        $this->assertContains('name', $variables);
    }

    public function test_get_variables_returns_empty_array_when_no_variables(): void
    {
        $content = 'This is a simple message with no variables';
        $variables = $this->service->getVariables($content);

        $this->assertEmpty($variables);
    }

    public function test_get_variables_handles_empty_content(): void
    {
        $variables = $this->service->getVariables('');

        $this->assertEmpty($variables);
    }

    // ============ validate Tests ============

    public function test_validate_accepts_valid_template(): void
    {
        $this->service->validate('Test Template', 'Hello {name}');
        $this->assertTrue(true); // No exception thrown
    }

    public function test_validate_rejects_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template name cannot be empty');

        $this->service->validate('', 'Hello {name}');
    }

    public function test_validate_rejects_whitespace_only_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template name cannot be empty');

        $this->service->validate('   ', 'Hello {name}');
    }

    public function test_validate_rejects_empty_content(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template content cannot be empty');

        $this->service->validate('Test Template', '');
    }

    public function test_validate_rejects_whitespace_only_content(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template content cannot be empty');

        $this->service->validate('Test Template', '   ');
    }

    public function test_validate_rejects_content_exceeding_max_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template content exceeds maximum length of 4096 characters');

        $longContent = str_repeat('a', 4097);
        $this->service->validate('Test Template', $longContent);
    }

    public function test_validate_accepts_content_at_max_length(): void
    {
        $maxContent = str_repeat('a', 4096);
        $this->service->validate('Test Template', $maxContent);
        $this->assertTrue(true); // No exception thrown
    }

    // ============ render Tests ============

    public function test_render_replaces_single_variable(): void
    {
        $template = $this->createMockTemplate('Hello {name}');
        $result = $this->service->render($template, ['name' => 'John']);

        $this->assertEquals('Hello John', $result);
    }

    public function test_render_replaces_multiple_variables(): void
    {
        $template = $this->createMockTemplate('Hello {name}, your status is {status}');
        $result = $this->service->render($template, [
            'name' => 'John',
            'status' => 'active',
        ]);

        $this->assertEquals('Hello John, your status is active', $result);
    }

    public function test_render_handles_missing_variables_with_empty_string(): void
    {
        $template = $this->createMockTemplate('Hello {name}, your status is {status}');
        $result = $this->service->render($template, ['name' => 'John']);

        $this->assertEquals('Hello John, your status is ', $result);
    }

    public function test_render_handles_all_missing_variables(): void
    {
        $template = $this->createMockTemplate('Hello {name}, your status is {status}');
        $result = $this->service->render($template, []);

        $this->assertEquals('Hello , your status is ', $result);
    }

    public function test_render_converts_non_string_values_to_string(): void
    {
        $template = $this->createMockTemplate('Count: {count}, Active: {active}');
        $result = $this->service->render($template, [
            'count' => 42,
            'active' => true,
        ]);

        $this->assertEquals('Count: 42, Active: 1', $result);
    }

    public function test_render_handles_null_values(): void
    {
        $template = $this->createMockTemplate('Value: {value}');
        $result = $this->service->render($template, ['value' => null]);

        $this->assertEquals('Value: ', $result);
    }

    public function test_render_handles_repeated_variables(): void
    {
        $template = $this->createMockTemplate('Hello {name}, welcome {name}. Your name is {name}.');
        $result = $this->service->render($template, ['name' => 'Alice']);

        $this->assertEquals('Hello Alice, welcome Alice. Your name is Alice.', $result);
    }

    public function test_render_ignores_extra_context_values(): void
    {
        $template = $this->createMockTemplate('Hello {name}');
        $result = $this->service->render($template, [
            'name' => 'John',
            'unused' => 'value',
            'extra' => 'data',
        ]);

        $this->assertEquals('Hello John', $result);
    }

    public function test_render_preserves_content_without_variables(): void
    {
        $template = $this->createMockTemplate('This is a simple message');
        $result = $this->service->render($template, []);

        $this->assertEquals('This is a simple message', $result);
    }

    public function test_render_handles_special_characters_in_values(): void
    {
        $template = $this->createMockTemplate('Message: {message}');
        $result = $this->service->render($template, [
            'message' => 'Hello & goodbye! <script>alert("xss")</script>',
        ]);

        $this->assertEquals('Message: Hello & goodbye! <script>alert("xss")</script>', $result);
    }

    public function test_render_handles_multiline_content(): void
    {
        $content = "Hello {name},\nYour status is {status}\nThank you!";
        $template = $this->createMockTemplate($content);
        $result = $this->service->render($template, [
            'name' => 'John',
            'status' => 'active',
        ]);

        $expected = "Hello John,\nYour status is active\nThank you!";
        $this->assertEquals($expected, $result);
    }

    public function test_render_handles_empty_context(): void
    {
        $template = $this->createMockTemplate('Hello {name}');
        $result = $this->service->render($template, []);

        $this->assertEquals('Hello ', $result);
    }

    // ============ Integration Tests ============

    public function test_full_workflow_with_validation_and_rendering(): void
    {
        $name = 'Invoice Reminder';
        $content = 'Dear {customer_name}, your invoice {invoice_id} is due on {due_date}';

        // Validate
        $this->service->validate($name, $content);

        // Extract variables
        $variables = $this->service->getVariables($content);
        $this->assertCount(3, $variables);

        // Render
        $template = $this->createMockTemplate($content);
        $result = $this->service->render($template, [
            'customer_name' => 'Acme Corp',
            'invoice_id' => 'INV-2026-001',
            'due_date' => '2026-05-26',
        ]);

        $expected = 'Dear Acme Corp, your invoice INV-2026-001 is due on 2026-05-26';
        $this->assertEquals($expected, $result);
    }

    public function test_template_types_are_supported(): void
    {
        $templates = [
            'notification' => 'Your order {order_id} has been shipped',
            'promo' => 'Special offer for {customer_name}: {discount}% off',
            'reminder' => 'Reminder: {event_name} is tomorrow at {time}',
        ];

        foreach ($templates as $type => $content) {
            $this->service->validate("Test {$type}", $content);
            $variables = $this->service->getVariables($content);
            $this->assertNotEmpty($variables);
        }
    }

    public function test_render_with_logger_dependency_injection(): void
    {
        $mockLogger = \Mockery::mock('Psr\Log\LoggerInterface');
        $mockLogger->shouldReceive('warning')->once();

        $service = new TemplateService($mockLogger);
        $template = $this->createMockTemplate('Hello {name}');
        $service->render($template, []);
    }

    private function createMockTemplate(string $content): Template
    {
        $template = new Template;
        $template->id = 1;
        $template->name = 'Test Template';
        $template->type = 'notification';
        $template->content = $content;
        $template->variables = $this->service->getVariables($content);

        return $template;
    }
}
