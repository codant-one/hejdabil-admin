<?php

namespace App\Services;

use App\Models\User;

class NotificationTemplateRenderer
{
    /** {{user}}, {{ user }}, {{USER}}, ... */
    private const PLACEHOLDER = '\{\{\s*user\s*\}\}';

    public function render(string $template, ?User $user): string
    {
        return $this->renderWithName($template, $user ? $this->resolveName($user) : '');
    }

    public function renderWithName(string $template, ?string $name): string
    {
        $name = trim((string) $name);

        if ($name !== '') {
            return preg_replace_callback('/' . self::PLACEHOLDER . '/iu', fn () => $name, $template);
        }

        $placeholders = '[ \t]*(?:' . self::PLACEHOLDER . '[ \t]*)+';

        // "Hej {{user}}, ..." -> "Hej, ..." (no space before punctuation)
        $result = preg_replace('/' . $placeholders . '(?=[,.!?;:])/iu', '', $template);
        // "Hej {{user}} och {{ USER }} välkommen" -> "Hej och välkommen"
        $result = preg_replace('/' . $placeholders . '/iu', ' ', $result);

        return trim($result);
    }

    /**
     * Full name: users.name + users.last_name (whichever exists); '' if both are empty.
     */
    public function resolveName(User $user): string
    {
        return trim(preg_replace('/\s+/u', ' ', trim((string) $user->name) . ' ' . trim((string) $user->last_name)));
    }
}
