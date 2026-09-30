<?php

namespace App\Support;

final class EvidenceDomId
{
    public static function fromRef(string $reference): string
    {
        $normalized = trim($reference);
        $slug = strtolower($normalized);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'ref';
        }

        return 'evidence-'.$slug.'-'.substr(hash('sha256', $normalized), 0, 10);
    }
}
