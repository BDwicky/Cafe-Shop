<?php

namespace Database\Seeders;

use App\Models\MusicDefaultTrack;
use Illuminate\Database\Seeder;

class MusicDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $tracks = [
            // Long Duration Cafe Mixes (1 - 3 Jam)
            [
                'title' => '1 A.M Study Session [1 Jam Lo-Fi Chill Cafe Beats]',
                'artist' => 'Lofi Girl',
                'youtube_id' => 'lTRiuFIWV54',
                'duration_seconds' => 3700,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Relaxing Jazz Piano Cafe Radio [2 Jam Slow Jazz Santai]',
                'artist' => 'Cafe Music BGM channel',
                'youtube_id' => 'Dx5qFachd3A',
                'duration_seconds' => 7200,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Sunny Mornings: Acoustic Guitar & Piano [2 Jam Cafe Vibe]',
                'artist' => 'Soothing Relaxation',
                'youtube_id' => 'hlWiI4xVXKY',
                'duration_seconds' => 7200,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Chillhop Cafe Radio [2 Jam Jazzy & Lo-Fi Beats]',
                'artist' => 'Chillhop Music',
                'youtube_id' => '5yx6BWlEVcY',
                'duration_seconds' => 7200,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Deep Focus & Study Alpha Waves [3 Jam Cafe Ambience]',
                'artist' => 'Yellow Brick Cinema',
                'youtube_id' => 'WPni755-Krg',
                'duration_seconds' => 10800,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Tokyo Coffee Shop Vibes [2 Jam Jazz & Lo-Fi Beats]',
                'artist' => 'Abao in Tokyo',
                'youtube_id' => 'kgx4WGK0oNU',
                'duration_seconds' => 7200,
                'sort_order' => 6,
                'is_active' => true,
            ],

            // Curated Short Songs (Standar 3 - 4 Menit)
            [
                'title' => 'TULUS - Teh Hijau (Official Lyric Video)',
                'artist' => 'Tulus',
                'youtube_id' => 'RO75uUZiAw0',
                'duration_seconds' => 211,
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'title' => 'Stephen Sanchez - Until I Found You',
                'artist' => 'Stephen Sanchez',
                'youtube_id' => 'GxldQ9GyXLA',
                'duration_seconds' => 177,
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'title' => 'Billie Eilish - Ocean Eyes (Official Music Video)',
                'artist' => 'Billie Eilish',
                'youtube_id' => 'viimfQi_pUw',
                'duration_seconds' => 200,
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'title' => 'NIKI - Every Summertime',
                'artist' => 'NIKI',
                'youtube_id' => 'QFGpLdF_5B8',
                'duration_seconds' => 215,
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'title' => 'Bruno Major - Nothing',
                'artist' => 'Bruno Major',
                'youtube_id' => 'u2X_Z_QzR_g',
                'duration_seconds' => 162,
                'sort_order' => 11,
                'is_active' => true,
            ],
        ];

        foreach ($tracks as $track) {
            MusicDefaultTrack::updateOrCreate(
                ['youtube_id' => $track['youtube_id']],
                $track
            );
        }
    }
}
