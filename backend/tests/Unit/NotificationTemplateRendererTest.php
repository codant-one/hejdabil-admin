<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NotificationTemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NotificationTemplateRendererTest extends TestCase
{
    private NotificationTemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new NotificationTemplateRenderer();
    }

    public static function placeholderVariants(): array
    {
        return [
            'compact' => ['Hej {{user}}!'],
            'with spaces' => ['Hej {{ user }}!'],
            'uppercase' => ['Hej {{USER}}!'],
            'mixed case with tabs' => ["Hej {{\tUsEr  }}!"],
        ];
    }

    #[DataProvider('placeholderVariants')]
    public function test_it_replaces_every_placeholder_variant(string $template): void
    {
        $this->assertSame('Hej Anna!', $this->renderer->renderWithName($template, 'Anna'));
    }

    public function test_it_replaces_multiple_placeholders_anywhere_in_the_text(): void
    {
        $this->assertSame(
            'Anna, välkommen! Vi har något nytt för dig, Anna.',
            $this->renderer->renderWithName('{{user}}, välkommen! Vi har något nytt för dig, {{ USER }}.', 'Anna')
        );
    }

    public function test_text_without_placeholder_is_unchanged(): void
    {
        $this->assertSame('Ny funktion  tillgänglig', $this->renderer->renderWithName('Ny funktion  tillgänglig', 'Anna'));
    }

    public function test_name_with_regex_special_characters_is_inserted_literally(): void
    {
        $this->assertSame('Hej $1 \\0 Anna!', $this->renderer->renderWithName('Hej {{user}}!', '$1 \\0 Anna'));
    }

    public function test_empty_name_removes_placeholder_without_double_spaces(): void
    {
        $this->assertSame('Hej och välkommen', $this->renderer->renderWithName('Hej {{user}} och välkommen', ''));
        $this->assertSame('Hej och välkommen', $this->renderer->renderWithName('Hej {{user}} {{ USER }} och välkommen', null));
        $this->assertSame('välkommen', $this->renderer->renderWithName('{{user}} välkommen', '   '));
        $this->assertSame('Hej', $this->renderer->renderWithName('Hej {{user}}', ''));
    }

    public function test_empty_name_does_not_leave_a_space_before_punctuation(): void
    {
        $this->assertSame('Hej, ny funktion!', $this->renderer->renderWithName('Hej {{user}}, ny funktion!', ''));
        $this->assertSame('Hej!', $this->renderer->renderWithName('Hej {{ user }}!', ''));
    }

    public function test_render_uses_name_and_last_name_of_the_user(): void
    {
        $user = new User(['name' => ' Anna ', 'last_name' => 'Svensson']);

        $this->assertSame('Hej Anna Svensson!', $this->renderer->render('Hej {{user}}!', $user));
    }

    public function test_render_uses_whatever_part_of_the_name_exists(): void
    {
        $this->assertSame('Hej Svensson!', $this->renderer->render('Hej {{user}}!', new User(['name' => '', 'last_name' => 'Svensson'])));
        $this->assertSame('Hej Anna!', $this->renderer->render('Hej {{user}}!', new User(['name' => 'Anna', 'last_name' => null])));
    }

    public function test_render_removes_placeholder_when_user_has_no_name(): void
    {
        $user = new User(['name' => '', 'last_name' => null]);

        $this->assertSame('Hej, välkommen', $this->renderer->render('Hej {{user}}, välkommen', $user));
        $this->assertSame('Hej, välkommen', $this->renderer->render('Hej {{user}}, välkommen', null));
    }
}
