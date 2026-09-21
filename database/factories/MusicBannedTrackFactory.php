<?php

namespace Database\Factories;

use App\Models\MusicBannedTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicBannedTrack>
 */
class MusicBannedTrackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = MusicBannedTrack::class;

    public function definition(): array
    {
        return [
            'youtube_id' => fake()->regexify('[A-Za-z0-9_-]{11}'),
            'title' => fake()->words(3, true),
            'artist' => fake()->name(),
            'reason' => 'Dilarang oleh kasir',
            'banned_by' => 'kasir',
            'is_active' => true,
        ];
    }
}
