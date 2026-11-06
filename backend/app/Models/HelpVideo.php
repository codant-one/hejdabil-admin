<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'help_category_id', 'state_id', 'title', 'description',
        'video_url', 'provider', 'thumbnail_url',
    ];

    protected $casts = ['help_category_id' => 'integer', 'state_id' => 'integer'];

    protected $appends = ['embed_url'];

    /**** Relationship ****/
    public function category() {
        return $this->belongsTo(HelpCategory::class, 'help_category_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($query) use ($search) {
            $query->where('title', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%')
                  ->orWhere('video_url', 'LIKE', '%' . $search . '%')
                  ->orWhere('provider', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('state_id') !== null) {
            $query->where('state_id', $filters->get('state_id'));
        }

        if ($filters->get('help_category_id') !== null) {
            $query->where('help_category_id', $filters->get('help_category_id'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'order_id';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit) {
        if ($limit == 'all') {
            return collect(['data' => $query->get()]);
        }

        return $query->paginate($limit);
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

    /**** Public methods ****/
    public static function createHelpVideo($request) {

        $helpVideo = self::create([
            'help_category_id' => $request->help_category_id,
            'state_id' => $request->state_id,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'provider' => $request->provider,
            'thumbnail_url' => $request->input('thumbnail_url', '')
        ]);
        
        return $helpVideo;
    }

    public static function updateHelpVideo($request, $helpVideo) {

        $data = [
            'help_category_id' => $request->help_category_id,
            'state_id' => $request->state_id,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'provider' => $request->provider,
        ];

        if ($request->filled('thumbnail_url')) {
            $data['thumbnail_url'] = $request->thumbnail_url;
        }

        $helpVideo->update($data);

        return $helpVideo;
    }

    public static function deleteHelpVideo($id) {
        self::deleteHelpVideos(array($id));
    }

    public static function deleteHelpVideos($ids) {
        foreach ($ids as $id) {
            $helpVideo = self::find($id);
            $helpVideo->delete();

            if($helpVideo->thumbnail_url)
                deleteFile($helpVideo->thumbnail_url);
        }
    }

}
