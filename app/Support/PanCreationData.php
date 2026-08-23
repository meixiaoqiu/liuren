<?php

namespace App\Support;

class PanCreationData
{
    public const EXPLAIN_DEFAULT = '无';

    public static function fromCalculatedPan(array $pan, string $shichen): array
    {
        $record = ['shichen' => $shichen];

        $record['sizhu'] = self::buildSizhu($pan);

        foreach (['yuejiang', 'niangan', 'nianzhi', 'yuegan', 'yuezhi', 'rigan', 'rizhi', 'shigan', 'shizhi'] as $key) {
            $record[$key] = $pan[$key];
        }

        foreach (range(0, 11) as $index) {
            $record["tianpan{$index}"] = $pan['tianpan'][$index];
        }

        foreach (range(0, 7) as $index) {
            $record["sike{$index}"] = $pan['sike'][$index];
        }

        foreach (range(0, 2) as $index) {
            $record["sanchuan{$index}"] = $pan["sanchuan{$index}"];
        }

        foreach (range(0, 11) as $index) {
            $record["tianjiang{$index}"] = $pan['tianjiang'][$index];
        }

        foreach (range(0, 2) as $index) {
            $record["xundun{$index}"] = $pan["xundun{$index}"];
            $record["liuqin{$index}"] = $pan["liuqin{$index}"];
            $record["sanchuan{$index}tianjiang"] = $pan["sanchuan{$index}tianjiang"];
        }

        $record['jiuzongmen'] = $pan['jiuzongmen'];
        $record['xingnian'] = 0;
        $record['nianming'] = 0;
        $record['explain'] = self::EXPLAIN_DEFAULT;

        return $record;
    }

    public static function buildSizhu(array $pan): int
    {
        return (int) (
            '1'
            .$pan['niangan']
            .str_pad((string) $pan['nianzhi'], 2, '0', STR_PAD_LEFT)
            .$pan['yuegan']
            .str_pad((string) $pan['yuezhi'], 2, '0', STR_PAD_LEFT)
            .$pan['rigan']
            .str_pad((string) $pan['rizhi'], 2, '0', STR_PAD_LEFT)
            .$pan['shigan']
            .str_pad((string) $pan['shizhi'], 2, '0', STR_PAD_LEFT)
        );
    }

    public static function stableHash(array $data): string
    {
        self::sortKeys($data);

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function sortKeys(array &$data): void
    {
        ksort($data);

        foreach ($data as &$value) {
            if (is_array($value)) {
                self::sortKeys($value);
            }
        }
    }
}
