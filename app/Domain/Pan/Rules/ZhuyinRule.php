<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》判断天魁戌、太乙巳同入三传所成的铸印课。 */
final class ZhuyinRule implements PanRule
{
    protected const RULE_CODE = 'lesson.zhuyin';

    protected const NAME = '铸印课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '天魁戌与太乙巳同入三传；戌为印、巳为炉。';

    protected const GUA = '鼎';

    protected const GUA_SYMBOL = '䷱';

    protected const XIANG = '顽金铸篆，藉火功全，官职高擢，诏命重宣，产孕大吉，干谒良缘，庶人不吉，疾病官愆。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        // 戌、巳同入三传（戌=10，巳=5）。
        if (! in_array(10, $transmissions, true) || ! in_array(5, $transmissions, true)) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'foundations' => [
                    [
                        'title' => '戌入传',
                        'detail' => '天魁戌（为印）入于三传。',
                    ],
                    [
                        'title' => '巳入传',
                        'detail' => '太乙巳（为炉）入于三传。',
                    ],
                ],
                'judgments' => $this->judgments($facts),
            ],
        );
    }

    /** @return list<array{code: string, effect: string, label: string, evidence: string}> */
    private function judgments(PanFacts $facts): array
    {
        $judgments = [];
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];
        $xundun = [
            $facts->get('xundun0'),
            $facts->get('xundun1'),
            $facts->get('xundun2'),
        ];
        $positionNames = ['初传', '中传', '末传'];
        $emptyDetails = [];

        // 戌（印）或卯（模）落旬空，则为破印损模。
        foreach ($transmissions as $position => $branch) {
            if ($branch === 10 && ($xundun[$position] === 10 || $xundun[$position] === 11)) {
                $emptyDetails[] = $positionNames[$position].'戌落旬空';
            }

            if ($branch === 3 && ($xundun[$position] === 10 || $xundun[$position] === 11)) {
                $emptyDetails[] = $positionNames[$position].'卯落旬空';
            }
        }

        if ($emptyDetails !== []) {
            $judgments[] = [
                'code' => 'seal_or_mold_empty',
                'effect' => 'reduce',
                'label' => '戌卯落空亡（破印损模）',
                'evidence' => implode('，', $emptyDetails).'，原文主破印损模。',
            ];
        }

        return $judgments;
    }
}
