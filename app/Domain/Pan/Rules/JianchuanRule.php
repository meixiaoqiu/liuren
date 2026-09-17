<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：判断第61课间传课，并结构化顺逆方向、二十四格及“日用旺相/休囚”修证。
 *
 * 冻结口径：
 * 1. 主体只看三传：初→中、中→末必须连续两次顺隔一位（+2）或连续两次逆隔一位（-2）；
 * 2. 二十四格是间传成立后的唯一结构分型，不反过来增加主体条件，也不简单等同于吉凶；
 * 3. “日用”按日干与用神（初传）解释；正文只写“旺相/休囚”，不擅自把“死”并入休囚；
 * 4. “神将吉/凶”的统一检查对象与集合尚未冻结；撞干、撞支也不以间传为成立前提，均不进入主体 matcher。
 */
final class JianchuanRule implements PanRule
{
    use LessonDefinitionDefaults;

    public const RULE_CODE = 'lesson.jianchuan';

    public const NAME = '间传课';

    public const GROUP = '六十四课';

    public const GUA = '巽';

    public const GUA_SYMBOL = '䷸';

    public const DESCRIPTION = '三传连续两次每隔一位递传：顺行两次各进二支，或逆行两次各退二支，为间传课。';

    public const XIANG = '间位相传，事多间阻。顺有登天、向阳、出户，逆有回阳、励明、顾祖。占者逢之，皆为吉课。';

    /**
     * 二十四格严格由三传唯一决定。
     *
     * @var array<string, array{code: string, label: string, description: string}>
     */
    public const SUBTYPES = [
        '4,6,8' => [
            'code' => 'deng_santian',
            'label' => '登三天格',
            'description' => '辰午申。官登天位主迁转；惟忌空脱。争讼事情转大，占病症候弥深，贼来，行人至，久旱则雨。',
        ],
        '6,8,10' => [
            'code' => 'chu_santian',
            'label' => '出三天格',
            'description' => '午申戌。事情趋于远大，但有亢极之象；出行失约、病讼多不利。',
        ],
        '8,10,0' => [
            'code' => 'she_sanyuan',
            'label' => '涉三渊格',
            'description' => '申戌子。主目前多阻隔，谋望难成，官事不利，病讼危险。',
        ],
        '10,0,2' => [
            'code' => 'ru_sanyuan',
            'label' => '入三渊格',
            'description' => '戌子寅。正文断“凡举皆凶”，有履险临危之象；若末传再见蛇虎鬼煞更凶。',
        ],
        '0,2,4' => [
            'code' => 'xiangyang',
            'label' => '向阳格',
            'description' => '子寅辰。由幽暗转向日出之方，主自暗入明，初凶后吉，病可愈、讼可解。',
        ],
        '2,4,6' => [
            'code' => 'chuyang',
            'label' => '出阳格',
            'description' => '寅辰午。由三阳而至午后阴生，正文主灾咎相仍，病讼多凶。',
        ],
        '1,3,5' => [
            'code' => 'chuhu',
            'label' => '出户格',
            'description' => '丑卯巳。有出门离户之象；访人多不在、行人已出，君子干望较利，小人多狐疑。',
        ],
        '3,5,7' => [
            'code' => 'yingyang',
            'label' => '盈阳格',
            'description' => '卯巳未。阳气至盈而将反，事情宜急就，迟缓则转凶。',
        ],
        '5,7,9' => [
            'code' => 'bianying',
            'label' => '变盈格',
            'description' => '巳未酉。物满必缺、势过人衰；凡占皆凶，占官被黜，占物非当时用者，占新病死，久病愈。',
        ],
        '7,9,11' => [
            'code' => 'ruming',
            'label' => '入冥格',
            'description' => '未酉亥。明消暗长，事情宜速办不宜缓；病讼、官事多不利。',
        ],
        '9,11,1' => [
            'code' => 'ningyin',
            'label' => '凝阴格',
            'description' => '酉亥丑。阴气凝结，主幽暗不明，并多见淫欲、奸盗等隐情。',
        ],
        '11,1,3' => [
            'code' => 'mingmeng',
            'label' => '溟濛格',
            'description' => '亥丑卯。阴极而阳始生，事情真假未明，主忧惧不宁、进退未决。',
        ],
        '2,0,10' => [
            'code' => 'mingyin',
            'label' => '冥阴格',
            'description' => '寅子戌。由明入暗，凶暗在前，须防暗损，占官尤忌。',
        ],
        '0,10,8' => [
            'code' => 'yanjian',
            'label' => '偃蹇格',
            'description' => '子戌申。以阴入阴，历涉艰难，如重遭荆棘；行军、出入、作为多不利。',
        ],
        '10,8,6' => [
            'code' => 'beili',
            'label' => '悖戾格',
            'description' => '戌申午。由深阴退向浅阴，有勉强后退之象；行人未至、贼不来，作事易成祸。',
        ],
        '8,6,4' => [
            'code' => 'ningyang',
            'label' => '凝阳格',
            'description' => '申午辰。阳凝于阴，灾事尚有牵系；旧事未了、行人来迟、讼事留连、谋事迟滞。',
        ],
        '6,4,2' => [
            'code' => 'guzu',
            'label' => '顾祖格',
            'description' => '午辰寅。如子孙回顾长生之祖，有复旧之象；求财谋望皆吉，贼去，行人来；惟庚日占病凶，占官大吉。',
        ],
        '4,2,0' => [
            'code' => 'sheyi',
            'label' => '涉疑格',
            'description' => '辰寅子。阳当进而反退，又由明入暗，主疑难不决；出行、关渡、安营、官病多不利。',
        ],
        '1,11,9' => [
            'code' => 'jiyin',
            'label' => '极阴格',
            'description' => '丑亥酉。阴退至极，正文多主酒色、淫泆、奸乱等事，病讼亦凶。',
        ],
        '11,9,7' => [
            'code' => 'shidun',
            'label' => '时遁格',
            'description' => '亥酉未。有潜形隐遁之象；占行人不来，出行不出，捕盗不获，贼去不来，君子吉而小人凶。',
        ],
        '9,7,5' => [
            'code' => 'liming',
            'label' => '励明格',
            'description' => '酉未巳。从暗入明，有历阴暗而后得明之象；凡举皆由勉强而后去，君子利取禄位，小人宜早营运。',
        ],
        '7,5,3' => [
            'code' => 'huiming',
            'label' => '回明格',
            'description' => '未巳卯。由阴至阳，如缺月渐回；宜迟进不宜骤举，吉事渐成、凶事渐消。',
        ],
        '5,3,1' => [
            'code' => 'zhuanbei',
            'label' => '转悖格',
            'description' => '巳卯丑。避明向暗，以巧就拙，乘正归邪，事转悖戾；主家零身怯、怪梦，作事似邪魔随事，好出头而不知省检，守分以安命。',
        ],
        '3,1,11' => [
            'code' => 'duanjian',
            'label' => '断涧格',
            'description' => '卯丑亥。一阳深入二阴，明消暗长；正文主君子退职、小人遇凶。',
        ],
    ];

    private const UNCOVERED = [
        '“神将吉/神将凶”的检查对象、天将集合及作用范围尚待跨课统一，不在本课自行创造口径',
        '撞干格、撞支格虽紧接间传二十四格出现，但并不以间传为成立前提，应另作独立结构研究',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function definition(): array
    {
        return [
            'description' => self::DESCRIPTION,
            'xiang' => self::XIANG,
            'foundations' => [
                [
                    'code' => 'jianchuan_step',
                    'title' => '三传连续两次隔一位递传',
                    'description' => '满足任一方向即可：① 初传到中传、中传到末传均顺行二支；② 两段均逆行二支。不能一顺一逆，也不能只满足其中一段。',
                ],
            ],
            'judgments' => [
                [
                    'code' => 'forward_jianchuan',
                    'effect' => 'neutral',
                    'label' => '顺间传',
                    'description' => '三传连续两次顺行二支；正文总断“顺主事顺”，但具体吉凶仍须依二十四格及课内修证判断。',
                ],
                [
                    'code' => 'reverse_jianchuan',
                    'effect' => 'neutral',
                    'label' => '逆间传',
                    'description' => '三传连续两次逆行二支；正文总断“逆主事逆”，但顾祖、回明等逆格本身仍可有吉义，不能直接等同于凶。',
                ],
                [
                    'code' => 'day_initial_wang_xiang',
                    'effect' => 'increase',
                    'label' => '日用俱旺相',
                    'description' => '日干与用神（初传）的时令状态都属于旺或相；正文说“如日用旺相……凡事吉利”。',
                ],
                [
                    'code' => 'day_initial_xiu_qiu',
                    'effect' => 'reduce',
                    'label' => '日用俱休囚',
                    'description' => '日干与用神（初传）的时令状态都属于休或囚；正文只写“休囚”，本实现不擅自把“死”并入此条件。',
                ],
            ],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = [];
        foreach (['sanchuan0', 'sanchuan1', 'sanchuan2'] as $key) {
            $branch = $facts->get($key);
            if (! is_int($branch) || $branch < 0 || $branch > 11) {
                return null;
            }
            $transmissions[] = $branch;
        }

        [$initial, $middle, $final] = $transmissions;
        $step1 = ($middle - $initial + 12) % 12;
        $step2 = ($final - $middle + 12) % 12;
        $forward = $step1 === 2 && $step2 === 2;
        $reverse = $step1 === 10 && $step2 === 10;

        if (! $forward && ! $reverse) {
            return null;
        }

        $subtype = self::SUBTYPES[implode(',', $transmissions)] ?? null;
        if ($subtype === null) {
            throw new \LogicException('间传课已成立但未落入二十四格映射。');
        }

        $dayStem = $facts->get('rigan');
        $dayState = is_int($dayStem) && $dayStem >= 0 && $dayStem <= 9
            ? $facts->stemSeasonalState($dayStem)
            : null;
        $initialState = $facts->branchSeasonalState($initial);

        $dayInitialWangXiang = in_array($dayState, ['旺', '相'], true)
            && in_array($initialState, ['旺', '相'], true);
        $dayInitialXiuQiu = in_array($dayState, ['休', '囚'], true)
            && in_array($initialState, ['休', '囚'], true);

        $branchName = static fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?';
        $transmissionNames = implode('、', array_map($branchName, $transmissions));
        $directionLabel = $forward ? '顺间传' : '逆间传';

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'transmissions' => $transmissions,
                'step1' => $step1,
                'step2' => $step2,
                'direction' => $forward ? 'forward' : 'reverse',
                'subtype' => $subtype['code'],
                'subtype_label' => $subtype['label'],
                'day_seasonal_state' => $dayState,
                'initial_seasonal_state' => $initialState,
                'foundations' => [
                    [
                        'code' => 'jianchuan_step',
                        'title' => '三传连续两次隔一位递传',
                        'description' => '两段传递必须同向，并且每段都正好隔过一个地支。',
                        'matched' => true,
                        'evidence' => "三传为{$transmissionNames}；{$directionLabel}，两段步长分别为{$step1}、{$step2}。",
                    ],
                ],
                'judgments' => [
                    [
                        'code' => 'forward_jianchuan',
                        'effect' => 'neutral',
                        'label' => '顺间传',
                        'description' => '两段均顺行二支；正文总断“顺主事顺”。',
                        'matched' => $forward,
                        'evidence' => $forward ? "三传{$transmissionNames}连续顺隔一位递传。" : '当前盘不是顺间传。',
                    ],
                    [
                        'code' => 'reverse_jianchuan',
                        'effect' => 'neutral',
                        'label' => '逆间传',
                        'description' => '两段均逆行二支；正文总断“逆主事逆”。',
                        'matched' => $reverse,
                        'evidence' => $reverse ? "三传{$transmissionNames}连续逆隔一位递传。" : '当前盘不是逆间传。',
                    ],
                    [
                        'code' => 'day_initial_wang_xiang',
                        'effect' => 'increase',
                        'label' => '日用俱旺相',
                        'description' => '日干与初传均处旺或相。',
                        'matched' => $dayInitialWangXiang,
                        'evidence' => $dayInitialWangXiang
                            ? "日干时令{$dayState}，初传{$branchName($initial)}时令{$initialState}，二者俱属旺相。"
                            : '当前盘日干与初传未同时处于旺相。',
                    ],
                    [
                        'code' => 'day_initial_xiu_qiu',
                        'effect' => 'reduce',
                        'label' => '日用俱休囚',
                        'description' => '日干与初传均处休或囚；不把“死”并入此条件。',
                        'matched' => $dayInitialXiuQiu,
                        'evidence' => $dayInitialXiuQiu
                            ? "日干时令{$dayState}，初传{$branchName($initial)}时令{$initialState}，二者俱属休囚。"
                            : '当前盘日干与初传未同时处于休囚。',
                    ],
                ],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
