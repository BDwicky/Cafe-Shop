<?php

namespace Database\Factories;

use App\Models\MusicRequest;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicRequest>
 */
class MusicRequestFactory extends Factory
{
    protected $model = MusicRequest::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_name' => fake()->name(),
            'song_title' => fake()->words(3, true),
            'artist' => fake()->name(),
            'youtube_id' => 'dQw4w9WgXcQ',
            'thumbnail_url' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'duration_seconds' => 210,
            'status' => 'queued',
            'played_at' => null,
            'notes' => null,
        ];
    }
}
