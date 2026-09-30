<?php

namespace App\Support;

final class EvidenceDomId
{
    public static function fromRef(string $reference): string
    {
        $slug = strtolower(trim($reference));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return 'evidence-'.$slug;
    }
}
