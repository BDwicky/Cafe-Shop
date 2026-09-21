<?php

namespace App\Support;

class DeviceDetector
{
    public string $platform = 'Unknown';

    public string $browser = 'Browser';

    public string $deviceType = 'tablet';

    public function __construct(public ?string $userAgent = null)
    {
        $this->parse($userAgent ?: '');
    }

    public static function fromUserAgent(?string $userAgent): self
    {
        return new self($userAgent);
    }

    protected function parse(string $ua): void
    {
        $uaLower = strtolower($ua);

        // 1. Detect Platform & Device Type
        if (str_contains($uaLower, 'ipad') || (str_contains($uaLower, 'macintosh') && str_contains($uaLower, 'touch'))) {
            $this->platform = 'iPadOS';
            $this->deviceType = 'tablet';
        } elseif (str_contains($uaLower, 'iphone')) {
            $this->platform = 'iOS';
            $this->deviceType = 'mobile';
        } elseif (str_contains($uaLower, 'android')) {
            $this->platform = 'Android';
            $this->deviceType = str_contains($uaLower, 'mobile') ? 'mobile' : 'tablet';
        } elseif (str_contains($uaLower, 'windows')) {
            $this->platform = 'Windows';
            $this->deviceType = 'desktop';
        } elseif (str_contains($uaLower, 'macintosh') || str_contains($uaLower, 'mac os x')) {
            $this->platform = 'macOS';
            $this->deviceType = 'desktop';
        } elseif (str_contains($uaLower, 'linux')) {
            $this->platform = 'Linux';
            $this->deviceType = 'desktop';
        }

        // 2. Detect Browser
        if (str_contains($uaLower, 'edg/') || str_contains($uaLower, 'edge/')) {
            $this->browser = 'Edge';
        } elseif (str_contains($uaLower, 'opr/') || str_contains($uaLower, 'opera/')) {
            $this->browser = 'Opera';
        } elseif (str_contains($uaLower, 'chrome/') || str_contains($uaLower, 'crios/')) {
            $this->browser = 'Chrome';
        } elseif (str_contains($uaLower, 'firefox/') || str_contains($uaLower, 'fxios/')) {
            $this->browser = 'Firefox';
        } elseif (str_contains($uaLower, 'safari/') && ! str_contains($uaLower, 'chrome/')) {
            $this->browser = 'Safari';
        }
    }

    public function suggestName(int $sequenceNumber = 1): string
    {
        $prefix = match ($this->deviceType) {
            'tablet' => 'Tablet Kasir',
            'mobile' => 'HP Kasir',
            'desktop' => 'Terminal POS',
            default => 'Perangkat Kasir',
        };

        return "{$prefix} #{$sequenceNumber} ({$this->platform})";
    }
}
