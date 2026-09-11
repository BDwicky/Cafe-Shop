<?php

namespace Database\Factories;

use App\Models\MusicDefaultTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicDefaultTrack>
 */
class MusicDefaultTrackFactory extends Factory
{
    protected $model = MusicDefaultTrack::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'artist' => fake()->name(),
            'youtube_id' => 'jfKfPfyJRdk', // lofi chill sample
            'duration_seconds' => 180,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
