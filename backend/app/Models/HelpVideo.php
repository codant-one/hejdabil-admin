<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'help_module_id', 'state_id', 'title', 'description',
        'video_url', 'provider', 'thumbnail_url',
    ];

    protected $casts = ['help_module_id' => 'integer', 'state_id' => 'integer'];

    protected $appends = ['embed_url'];

    /** @return BelongsTo<HelpModule, HelpVideo> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(HelpModule::class, 'help_module_id');
    }

    /** @return BelongsTo<State, HelpVideo> */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('state_id', 2);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim($term ?? '');
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%');
        });
    }

    public function scopeForModule(Builder $query, ?int $moduleId): Builder
    {
        return $moduleId === null ? $query : $query->where('help_module_id', $moduleId);
    }

    public function scopeOrdered(Builder $query, string $sort = 'newest'): Builder
    {
        [$column, $direction] = match ($sort) {
            'oldest' => ['created_at', 'asc'],
            'title_asc' => ['title', 'asc'],
            'title_desc' => ['title', 'desc'],
            default => ['created_at', 'desc'],
        };

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        $query->search($filters['search'] ?? null)
            ->forModule(isset($filters['module']) ? (int) $filters['module'] : null);

        if (isset($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        if (isset($filters['sort'])) {
            return $query->ordered($filters['sort']);
        }

        $column = $filters['orderByField'] ?? 'created_at';
        $direction = $filters['orderBy'] ?? 'desc';

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        return self::embedUrlFor($this->provider, $this->video_url);
    }

    public static function embedUrlFor(string $provider, string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        if ($provider === 'mp4') {
            return $url;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if ($provider === 'youtube') {
            parse_str($parts['query'] ?? '', $parameters);
            $id = null;
            if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
                $id = $path;
            } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
                if ($path === 'watch') {
                    $id = $parameters['v'] ?? null;
                } elseif (preg_match('~^(?:embed|shorts|live)/([^/]+)$~', $path, $matches)) {
                    $id = $matches[1];
                }
            }

            return is_string($id) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $id)
                ? 'https://www.youtube.com/embed/'.$id
                : null;
        }

        if ($provider === 'vimeo' && in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            if (preg_match('~^(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)(?:/([a-zA-Z0-9]+))?$~', $path, $matches)) {
                parse_str($parts['query'] ?? '', $parameters);
                $hash = $matches[2] ?? ($parameters['h'] ?? null);
                $embed = 'https://player.vimeo.com/video/'.$matches[1];

                return is_string($hash) && preg_match('/^[a-zA-Z0-9]+$/', $hash)
                    ? $embed.'?h='.$hash
                    : $embed;
            }
        }

        return null;
    }

    public static function createVideo(array $data): self
    {
        return self::create($data);
    }

    public static function updateVideo(array $data, self $video): self
    {
        $video->update($data);

        return $video;
    }

    public static function deleteVideo(self $video): void
    {
        $video->delete();
    }
}
