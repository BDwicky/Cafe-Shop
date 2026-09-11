<?php

namespace Database\Seeders;

use App\Models\MusicDefaultTrack;
use Illuminate\Database\Seeder;

class MusicDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $tracks = [
            [
                'title' => 'TULUS - Teh Hijau (Official Lyric Video)',
                'artist' => 'Tulus',
                'youtube_id' => 'RO75uUZiAw0',
                'duration_seconds' => 211,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Stephen Sanchez - Until I Found You',
                'artist' => 'Stephen Sanchez',
                'youtube_id' => 'GxldQ9GyXLA',
                'duration_seconds' => 177,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Billie Eilish - Ocean Eyes (Official Music Video)',
                'artist' => 'Billie Eilish',
                'youtube_id' => 'viimfQi_pUw',
                'duration_seconds' => 200,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'NIKI - Every Summertime',
                'artist' => 'NIKI',
                'youtube_id' => 'QFGpLdF_5B8',
                'duration_seconds' => 215,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Bruno Major - Nothing',
                'artist' => 'Bruno Major',
                'youtube_id' => 'u2X_Z_QzR_g',
                'duration_seconds' => 162,
                'sort_order' => 5,
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
