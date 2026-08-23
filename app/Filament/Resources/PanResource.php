<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PanResource\Pages;
use App\Models\Pan;
use BackedEnum;
use com\tyme\culture\Element;
use com\tyme\solar\SolarTime;
use DateTime;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PanResource extends Resource
{
    protected static ?string $model = Pan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    // 定义地支
    public static ?array $dizhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    // 定义天干
    public static ?array $tiangan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸', '空', '空'];

    // 定义五行
    public static ?array $wuxing = ['木', '火', '土', '金', '水'];

    // 定义天干的五行
    public static ?array $wuxingTian = [0, 0, 1, 1, 2, 2, 3, 3, 4, 4];

    // 定义地支的五行
    public static ?array $wuxingDi = [4, 2, 0, 0, 2, 1, 1, 2, 3, 3, 2, 4];

    // 定义天干的阴阳
    public static ?array $yinyangTian = [1, 0, 1, 0, 1, 0, 1, 0, 1, 0];

    // 定义地支的阴阳
    public static ?array $yinyangDi = [1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0];

    // 地支的刑
    public static ?array $xing = [3, 10, 5, 0, 4, 8, 6, 1, 2, 9, 7, 11];

    // 地支的相冲
    public static ?array $chong = [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5];

    // 定义月将
    public static ?array $yuejiang = [
        0 => '子/神后/大寒到雨水',
        1 => '丑/大吉/冬至到大寒',
        2 => '寅/功曹/小雪到冬至',
        3 => '卯/太冲/霜降到小雪',
        4 => '辰/天罡/秋分到霜降',
        5 => '巳/太乙/处暑到秋分',
        6 => '午/胜光/大暑到处暑',
        7 => '未/小吉/夏至到大暑',
        8 => '申/传送/小满到夏至',
        9 => '酉/从魁/谷雨到小满',
        10 => '戌/河魁/春分到谷雨',
        11 => '亥/登明/雨水到春分',
    ];

    // 定义天将
    public static ?array $tianjiang = ['贵人', '螣蛇', '朱雀', '六合', '勾陈', '青龙', '天空', '白虎', '太常', '玄武', '太阴', '天后'];

    // 定义九宗门名字
    public static ?array $jiuzongmen = ['未知', '元首', '重审', '比用', '比用知一', '涉害', '涉害见机', '涉害察微', '涉害缀瑕', '遥克蒿矢', '遥克弹射', '昴星虎视', '昴星冬蛇掩目', '别责', '八专', '八专独足', '伏吟不虞', '伏吟自任', '伏吟自信', '伏吟杜撰', '反吟无依', '反吟无亲'];

    // 定义六亲
    public static ?array $liuqin = [
        -2 => '子孙',
        -1 => '妻财',
        0 => '兄弟',
        1 => '官鬼',
        2 => '父母',
    ];

    // 定义每个节气对应的月将index
    public static ?array $jieqi2Yuejiang = [1, 1, 0, 0, 11, 11, 10, 10, 9, 9, 8, 8, 7, 7, 6, 6, 5, 5, 4, 4, 3, 3, 2, 2];

    // 定义十干寄宫
    public static ?array $jigong = [2, 4, 5, 7, 5, 7, 8, 10, 11, 1];

    // 定义六十甲子对应的干支
    public static ?array $jiazi2Ganzhi = [
        [0, 0],
        [1, 1],
        [2, 2],
        [3, 3],
        [4, 4],
        [5, 5],
        [6, 6],
        [7, 7],
        [8, 8],
        [9, 9],
        [0, 10],
        [1, 11], // 甲子 - 乙亥
        [2, 0],
        [3, 1],
        [4, 2],
        [5, 3],
        [6, 4],
        [7, 5],
        [8, 6],
        [9, 7],
        [0, 8],
        [1, 9],
        [2, 10],
        [3, 11], // 丙子 - 丁亥
        [4, 0],
        [5, 1],
        [6, 2],
        [7, 3],
        [8, 4],
        [9, 5],
        [0, 6],
        [1, 7],
        [2, 8],
        [3, 9],
        [4, 10],
        [5, 11], // 戊子 - 己亥
        [6, 0],
        [7, 1],
        [8, 2],
        [9, 3],
        [0, 4],
        [1, 5],
        [2, 6],
        [3, 7],
        [4, 8],
        [5, 9],
        [6, 10],
        [7, 11], // 庚子 - 辛亥
        [8, 0],
        [9, 1],
        [0, 2],
        [1, 3],
        [2, 4],
        [3, 5],
        [4, 6],
        [5, 7],
        [6, 8],
        [7, 9],
        [8, 10],
        [9, 11],  // 壬子 - 癸亥
    ];

    // 定义小时转时辰
    public static ?array $hour2Shichen = [0, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 9, 10, 10, 11, 11, 0];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                SchemaComponents\Group::make([
                    SchemaComponents\Section::make()
                        ->schema([
                            Forms\Components\DateTimePicker::make('shichen')
                                ->label('时辰 北京时间')
                                ->seconds(false)
                                ->displayFormat('Y-m-d H:i:s')
                                ->required()
                                ->default(function (Get $get, Set $set): string {
                                    $now = now('Asia/Shanghai');
                                    if (! empty(request()->query('sc'))) {
                                        $now = request()->query('sc');
                                    }
                                    // $now = '2024-08-11 14:00:28.165631';
                                    self::qipan($now);

                                    return $now;
                                })
                                ->afterStateUpdated(function (Get $get, Set $set): string {
                                    self::qipan($get('shichen'));

                                    $set('niangan', session('pan')['niangan']);
                                    $set('nianzhi', session('pan')['nianzhi']);
                                    $set('yuegan', session('pan')['yuegan']);
                                    $set('yuezhi', session('pan')['yuezhi']);
                                    $set('rigan', session('pan')['rigan']);
                                    $set('rizhi', session('pan')['rizhi']);
                                    $set('shigan', session('pan')['shigan']);
                                    $set('shizhi', session('pan')['shizhi']);

                                    $nianzhi = str_pad(session('pan')['nianzhi'], 2, '0', STR_PAD_LEFT);
                                    $yuezhi = str_pad(session('pan')['yuezhi'], 2, '0', STR_PAD_LEFT);
                                    $rizhi = str_pad(session('pan')['rizhi'], 2, '0', STR_PAD_LEFT);
                                    $shizhi = str_pad(session('pan')['shizhi'], 2, '0', STR_PAD_LEFT);

                                    $sizhu = (int) '1'.session('pan')['niangan'].$nianzhi
                                        .session('pan')['yuegan'].$yuezhi
                                        .session('pan')['rigan'].$rizhi
                                        .session('pan')['shigan'].$shizhi;
                                    $set('sizhu', $sizhu);

                                    return $get('shichen');
                                })
                                ->reactive(),

                            Forms\Components\Placeholder::make('四柱')
                                ->content(function (Get $get): string {
                                    if ($get('id') > 0) {
                                        return self::$tiangan[$get('niangan')].self::$dizhi[$get('nianzhi')].' / '
                                            .self::$tiangan[$get('yuegan')].self::$dizhi[$get('yuezhi')].' / '
                                            .self::$tiangan[$get('rigan')].self::$dizhi[$get('rizhi')].' / '
                                            .self::$tiangan[$get('shigan')].self::$dizhi[$get('shizhi')];
                                    } else {
                                        return session('pan')['sizhu'];
                                    }
                                }),

                            // 四柱
                            Forms\Components\Hidden::make('sizhu')
                                ->default(function (Set $set) {
                                    $nianzhi = str_pad(session('pan')['nianzhi'], 2, '0', STR_PAD_LEFT);
                                    $yuezhi = str_pad(session('pan')['yuezhi'], 2, '0', STR_PAD_LEFT);
                                    $rizhi = str_pad(session('pan')['rizhi'], 2, '0', STR_PAD_LEFT);
                                    $shizhi = str_pad(session('pan')['shizhi'], 2, '0', STR_PAD_LEFT);

                                    $sizhu = intval('1'.session('pan')['niangan'].$nianzhi
                                        .session('pan')['yuegan'].$yuezhi
                                        .session('pan')['rigan'].$rizhi
                                        .session('pan')['shigan'].$shizhi);
                                    $set('sizhu', $sizhu);

                                    return $sizhu;
                                }),
                            // 解盘字段默认为空
                            Forms\Components\Hidden::make('explain')
                                ->default('无'),
                            // 年干
                            Forms\Components\Hidden::make('niangan')
                                ->default(function (Set $set) {
                                    $set('niangan', session('pan')['niangan']);

                                    return session('pan')['niangan'];
                                }),
                            // 年支
                            Forms\Components\Hidden::make('nianzhi')
                                ->default(function (Set $set) {
                                    $set('nianzhi', session('pan')['nianzhi']);

                                    return session('pan')['nianzhi'];
                                }),
                            // 月干
                            Forms\Components\Hidden::make('yuegan')
                                ->default(function (Set $set) {
                                    $set('yuegan', session('pan')['yuegan']);

                                    return session('pan')['yuegan'];
                                }),
                            // 月支
                            Forms\Components\Hidden::make('yuezhi')
                                ->default(function (Set $set) {
                                    $set('yuezhi', session('pan')['yuezhi']);

                                    return session('pan')['yuezhi'];
                                }),
                            // 日干
                            Forms\Components\Hidden::make('rigan')
                                ->default(function (Set $set) {
                                    $set('rigan', session('pan')['rigan']);

                                    return session('pan')['rigan'];
                                }),
                            // 日支
                            Forms\Components\Hidden::make('rizhi')
                                ->default(function (Set $set) {
                                    $set('rizhi', session('pan')['rizhi']);

                                    return session('pan')['rizhi'];
                                }),
                            // 时干
                            Forms\Components\Hidden::make('shigan')
                                ->default(function (Set $set) {
                                    $set('shigan', session('pan')['shigan']);

                                    return session('pan')['shigan'];
                                }),
                            // 时支
                            Forms\Components\Hidden::make('shizhi')
                                ->default(function (Set $set) {
                                    $set('shizhi', session('pan')['shizhi']);

                                    return session('pan')['shizhi'];
                                }),

                            Forms\Components\Select::make('yuejiang')
                                ->label('月将')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('yuejiang') : session('pan')['yuejiang'];
                                    $set('yuejiang', $index);

                                    return [$index => self::$yuejiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('yuejiang', session('pan')['yuejiang']);

                                    return session('pan')['yuejiang'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),
                        ])
                        ->columns(3)
                        ->columnSpan(3),

                    SchemaComponents\Section::make()
                        ->schema([
                            Forms\Components\Select::make('liuqin0')
                                ->label('六亲0')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('liuqin0') : session('pan')['liuqin0'];
                                    $set('liuqin0', $index);

                                    return [$index => self::$liuqin[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('liuqin0', session('pan')['liuqin0']);

                                    return session('pan')['liuqin0'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('xundun0')
                                ->label('旬遁0')
                                ->options(function (Set $set, Get $get) {
                                    $xundun0 = $get('id') > 0 ? $get('xundun0') : session('pan')['xundun0'];
                                    $set('xundun0', $xundun0);

                                    return [$xundun0 => self::$tiangan[$xundun0]];
                                })
                                ->default(function (Set $set) {
                                    $set('xundun0', session('pan')['xundun0']);

                                    return session('pan')['xundun0'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('sanchuan0')
                                ->label('初传')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sanchuan0') : session('pan')['sanchuan0'];
                                    $set('sanchuan0', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sanchuan0', session('pan')['sanchuan0']);

                                    return session('pan')['sanchuan0'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('sanchuan0tianjiang')
                                ->label('初传天将')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sanchuan0tianjiang') : session('pan')['sanchuan0tianjiang'];
                                    $set('sanchuan0tianjiang', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sanchuan0tianjiang', session('pan')['sanchuan0tianjiang']);

                                    return session('pan')['sanchuan0tianjiang'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('liuqin1')
                                ->label('六亲1')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('liuqin1') : session('pan')['liuqin1'];
                                    $set('liuqin1', $index);

                                    return [$index => self::$liuqin[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('liuqin1', session('pan')['liuqin1']);

                                    return session('pan')['liuqin1'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('xundun1')
                                ->label('旬遁1')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('xundun1') : session('pan')['xundun1'];
                                    $set('xundun1', $index);

                                    return [$index => self::$tiangan[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('xundun1', session('pan')['xundun1']);

                                    return session('pan')['xundun1'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('sanchuan1')
                                ->label('中传')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sanchuan1') : session('pan')['sanchuan1'];
                                    $set('sanchuan1', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sanchuan1', session('pan')['sanchuan1']);

                                    return session('pan')['sanchuan1'];
                                })
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('sanchuan1tianjiang')
                                ->label('中传天将')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sanchuan1tianjiang') : session('pan')['sanchuan1tianjiang'];
                                    $set('sanchuan1tianjiang', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sanchuan1tianjiang', session('pan')['sanchuan1tianjiang']);

                                    return session('pan')['sanchuan1tianjiang'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('liuqin2')
                                ->label('六亲2')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('liuqin2') : session('pan')['liuqin2'];
                                    $set('liuqin2', $index);

                                    return [$index => self::$liuqin[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('liuqin2', session('pan')['liuqin2']);

                                    return session('pan')['liuqin2'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('xundun2')
                                ->label('旬遁2')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('xundun2') : session('pan')['xundun2'];
                                    $set('xundun2', $index);

                                    return [$index => self::$tiangan[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('xundun2', session('pan')['xundun2']);

                                    return session('pan')['xundun2'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('sanchuan2')
                                ->label('末传')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sanchuan2') : session('pan')['sanchuan2'];
                                    $set('sanchuan2', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sanchuan2', session('pan')['sanchuan2']);

                                    return session('pan')['sanchuan2'];
                                })
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('sanchuan2tianjiang')
                                ->label('末传天将')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sanchuan2tianjiang') : session('pan')['sanchuan2tianjiang'];
                                    $set('sanchuan2tianjiang', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sanchuan2tianjiang', session('pan')['sanchuan2tianjiang']);

                                    return session('pan')['sanchuan2tianjiang'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),
                        ])
                        ->columns(4)
                        ->columnSpan(3),

                    SchemaComponents\Section::make()
                        ->schema([
                            Forms\Components\Select::make('sike7')
                                ->label('四课7')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike7') : session('pan')['sike'][7];
                                    $set('sike7', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike7', session('pan')['sike'][7]);

                                    return session('pan')['sike'][7];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Placeholder::make('五行7')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike7') : session('pan')['sike'][7];

                                    return self::$wuxing[self::$wuxingDi[$index]];
                                }),

                            Forms\Components\Select::make('sike5')
                                ->label('四课5')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike5') : session('pan')['sike'][5];
                                    $set('sike5', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike5', session('pan')['sike'][5]);

                                    return session('pan')['sike'][5];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Placeholder::make('五行5')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike5') : session('pan')['sike'][5];

                                    return self::$wuxing[self::$wuxingDi[$index]];
                                }),

                            Forms\Components\Select::make('sike3')
                                ->label('四课3')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike3') : session('pan')['sike'][3];
                                    $set('sike3', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike3', session('pan')['sike'][3]);

                                    return session('pan')['sike'][3];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Placeholder::make('五行3')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike3') : session('pan')['sike'][3];

                                    return self::$wuxing[self::$wuxingDi[$index]];
                                }),

                            Forms\Components\Select::make('sike1')
                                ->label('四课1')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike1') : session('pan')['sike'][1];
                                    $set('sike1', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike1', session('pan')['sike'][1]);

                                    return session('pan')['sike'][1];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false)
                                ->live(),

                            Forms\Components\Placeholder::make('五行1')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike1') : session('pan')['sike'][1];

                                    return self::$wuxing[self::$wuxingDi[$index]];
                                }),

                            Forms\Components\Select::make('sike6')
                                ->label('四课6')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike6') : session('pan')['sike'][6];
                                    $set('sike6', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike6', session('pan')['sike'][6]);

                                    return session('pan')['sike'][6];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Placeholder::make('五行6')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike6') : session('pan')['sike'][6];

                                    return self::$wuxing[self::$wuxingDi[$index]];
                                }),

                            Forms\Components\Select::make('sike4')
                                ->label('四课4')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike4') : session('pan')['sike'][4];
                                    $set('sike4', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike4', session('pan')['sike'][4]);

                                    return session('pan')['sike'][4];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Placeholder::make('五行4')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike4') : session('pan')['sike'][4];

                                    return self::$wuxing[self::$wuxingDi[$index]];
                                }),

                            Forms\Components\Select::make('sike2')
                                ->label('四课2')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike2') : session('pan')['sike'][2];
                                    $set('sike2', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike2', session('pan')['sike'][2]);

                                    return session('pan')['sike'][2];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Placeholder::make('五行2')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike2') : session('pan')['sike'][2];

                                    return self::$wuxing[self::$wuxingDi[$index]];
                                }),

                            Forms\Components\Select::make('sike0')
                                ->label('四课0')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('sike0') : session('pan')['sike'][0];
                                    $set('sike0', $index);

                                    return [$index => self::$tiangan[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('sike0', session('pan')['sike'][0]);

                                    return session('pan')['sike'][0];
                                })
                                ->selectablePlaceholder(false),

                            Forms\Components\Placeholder::make('五行0')
                                ->content(function (Get $get): string {
                                    $index = $get('id') > 0 ? $get('sike0') : session('pan')['sike'][0];

                                    return self::$wuxing[self::$wuxingTian[$index]];
                                }),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Placeholder::make('生克关系')
                                ->content(function (Get $get): string {
                                    $indexUp = $get('id') > 0 ? $get('sike7') : session('pan')['sike'][7];
                                    $indexDown = $get('id') > 0 ? $get('sike6') : session('pan')['sike'][6];
                                    $shengke = self::getShengke(self::$wuxingDi[$indexUp], self::$wuxingDi[$indexDown]);

                                    return $shengke[1];
                                }),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Placeholder::make('生克关系')
                                ->content(function (Get $get): string {
                                    $indexUp = $get('id') > 0 ? $get('sike5') : session('pan')['sike'][5];
                                    $indexDown = $get('id') > 0 ? $get('sike4') : session('pan')['sike'][4];
                                    $shengke = self::getShengke(self::$wuxingDi[$indexUp], self::$wuxingDi[$indexDown]);

                                    return $shengke[1];
                                }),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Placeholder::make('生克关系')
                                ->content(function (Get $get): string {
                                    $indexUp = $get('id') > 0 ? $get('sike3') : session('pan')['sike'][3];
                                    $indexDown = $get('id') > 0 ? $get('sike2') : session('pan')['sike'][2];
                                    $shengke = self::getShengke(self::$wuxingDi[$indexUp], self::$wuxingDi[$indexDown]);

                                    return $shengke[1];
                                }),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Placeholder::make('生克关系')
                                ->content(function (Get $get): string {
                                    $indexUp = $get('id') > 0 ? $get('sike1') : session('pan')['sike'][1];
                                    $indexDown = $get('id') > 0 ? $get('sike0') : session('pan')['sike'][0];
                                    $shengke = self::getShengke(self::$wuxingDi[$indexUp], self::$wuxingTian[$indexDown]);

                                    return $shengke[1];
                                }),
                        ])
                        ->columns(8)
                        ->columnSpan(3),

                    SchemaComponents\Section::make()
                        ->schema([
                            Forms\Components\Select::make('tianpan5')
                                ->label('天盘5')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan5') : session('pan')['tianpan'][5];
                                    $set('tianpan5', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan5', session('pan')['tianpan'][5]);

                                    return session('pan')['tianpan'][5];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang5')
                                ->label('天将5')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang5') : session('pan')['tianjiang'][5];
                                    $set('tianjiang5', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang5', session('pan')['tianjiang'][5]);

                                    return session('pan')['tianjiang'][5];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianpan6')
                                ->label('天盘6')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan6') : session('pan')['tianpan'][6];
                                    $set('tianpan6', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan6', session('pan')['tianpan'][6]);

                                    return session('pan')['tianpan'][6];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang6')
                                ->label('天将6')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang6') : session('pan')['tianjiang'][6];
                                    $set('tianjiang6', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang6', session('pan')['tianjiang'][6]);

                                    return session('pan')['tianjiang'][6];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianpan7')
                                ->label('天盘7')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan7') : session('pan')['tianpan'][7];
                                    $set('tianpan7', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan7', session('pan')['tianpan'][7]);

                                    return session('pan')['tianpan'][7];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang7')
                                ->label('天将7')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang7') : session('pan')['tianjiang'][7];
                                    $set('tianjiang7', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang7', session('pan')['tianjiang'][7]);

                                    return session('pan')['tianjiang'][7];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianpan8')
                                ->label('天盘8')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan8') : session('pan')['tianpan'][8];
                                    $set('tianpan8', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan8', session('pan')['tianpan'][8]);

                                    return session('pan')['tianpan'][8];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang8')
                                ->label('天将8')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang8') : session('pan')['tianjiang'][8];
                                    $set('tianjiang8', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang8', session('pan')['tianjiang'][8]);

                                    return session('pan')['tianjiang'][8];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '巳',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '午',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '未',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '申',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Select::make('tianpan4')
                                ->label('天盘4')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan4') : session('pan')['tianpan'][4];
                                    $set('tianpan4', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan4', session('pan')['tianpan'][4]);

                                    return session('pan')['tianpan'][4];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang4')
                                ->label('天将4')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang4') : session('pan')['tianjiang'][4];
                                    $set('tianjiang4', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang4', session('pan')['tianjiang'][4]);

                                    return session('pan')['tianjiang'][4];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Select::make('tianpan9')
                                ->label('天盘9')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan9') : session('pan')['tianpan'][9];
                                    $set('tianpan9', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan9', session('pan')['tianpan'][9]);

                                    return session('pan')['tianpan'][9];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang9')
                                ->label('天将9')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang9') : session('pan')['tianjiang'][9];
                                    $set('tianjiang9', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang9', session('pan')['tianjiang'][9]);

                                    return session('pan')['tianjiang'][9];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '辰',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '酉',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Select::make('tianpan3')
                                ->label('天盘3')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan3') : session('pan')['tianpan'][3];
                                    $set('tianpan3', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan3', session('pan')['tianpan'][3]);

                                    return session('pan')['tianpan'][3];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang3')
                                ->label('天将3')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang3') : session('pan')['tianjiang'][3];
                                    $set('tianjiang3', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang3', session('pan')['tianjiang'][3]);

                                    return session('pan')['tianjiang'][3];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Select::make('tianpan10')
                                ->label('天盘10')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan10') : session('pan')['tianpan'][10];
                                    $set('tianpan10', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan10', session('pan')['tianpan'][10]);

                                    return session('pan')['tianpan'][10];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang10')
                                ->label('天将10')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang10') : session('pan')['tianjiang'][10];
                                    $set('tianjiang10', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang10', session('pan')['tianjiang'][10]);

                                    return session('pan')['tianjiang'][10];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '卯',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '戌',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            Forms\Components\Select::make('tianpan2')
                                ->label('天盘2')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan2') : session('pan')['tianpan'][2];
                                    $set('tianpan2', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan2', session('pan')['tianpan'][2]);

                                    return session('pan')['tianpan'][2];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang2')
                                ->label('天将2')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang2') : session('pan')['tianjiang'][2];
                                    $set('tianjiang2', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang2', session('pan')['tianjiang'][2]);

                                    return session('pan')['tianjiang'][2];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianpan1')
                                ->label('天盘1')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan1') : session('pan')['tianpan'][1];
                                    $set('tianpan1', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan1', session('pan')['tianpan'][1]);

                                    return session('pan')['tianpan'][1];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang1')
                                ->label('天将1')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang1') : session('pan')['tianjiang'][1];
                                    $set('tianjiang1', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang1', session('pan')['tianjiang'][1]);

                                    return session('pan')['tianjiang'][1];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianpan0')
                                ->label('天盘0')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan0') : session('pan')['tianpan'][0];
                                    $set('tianpan0', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan0', session('pan')['tianpan'][0]);

                                    return session('pan')['tianpan'][0];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang0')
                                ->label('天将0')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang0') : session('pan')['tianjiang'][0];
                                    $set('tianjiang0', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang0', session('pan')['tianjiang'][0]);

                                    return session('pan')['tianjiang'][0];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianpan11')
                                ->label('天盘11')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianpan11') : session('pan')['tianpan'][11];
                                    $set('tianpan11', $index);

                                    return [$index => self::$dizhi[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianpan11', session('pan')['tianpan'][11]);

                                    return session('pan')['tianpan'][11];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            Forms\Components\Select::make('tianjiang11')
                                ->label('天将11')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('tianjiang11') : session('pan')['tianjiang'][11];
                                    $set('tianjiang11', $index);

                                    return [$index => self::$tianjiang[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('tianjiang11', session('pan')['tianjiang'][11]);

                                    return session('pan')['tianjiang'][11];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '寅',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '丑',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '子',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => '亥',
                                ]),

                            ViewField::make('dipan')
                                ->view('pan.dipan')
                                ->viewData([
                                    'dizhi' => ' ',
                                ]),
                        ])
                        ->columns(8)
                        ->columnSpan(3),
                ])
                    ->columnSpan(['lg' => 2]),

                SchemaComponents\Group::make([
                    SchemaComponents\Section::make()
                        ->schema([
                            Forms\Components\Select::make('jiuzongmen')
                                ->label('九宗门')
                                ->options(function (Set $set, Get $get) {
                                    $index = $get('id') > 0 ? $get('jiuzongmen') : session('pan')['jiuzongmen'];
                                    $set('jiuzongmen', $index);

                                    return [$index => self::$jiuzongmen[$index]];
                                })
                                ->default(function (Set $set) {
                                    $set('jiuzongmen', session('pan')['jiuzongmen']);

                                    return session('pan')['jiuzongmen'];
                                })
                                ->reactive()
                                ->selectablePlaceholder(false),
                        ]),
                ])
                    ->columnSpan(['lg' => 1]),

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('shichen'),
                Tables\Columns\TextColumn::make('sizhu')
                    ->formatStateUsing(function ($state) {
                        $arr = str_split($state);

                        return self::$tiangan[$arr[1]].self::$dizhi[intval($arr[2].$arr[3])]
                            .self::$tiangan[$arr[4]].self::$dizhi[intval($arr[5].$arr[6])]
                            .self::$tiangan[$arr[7]].self::$dizhi[intval($arr[8].$arr[9])]
                            .self::$tiangan[$arr[10]].self::$dizhi[intval($arr[11].$arr[12])];
                    }),
                Tables\Columns\TextColumn::make('yuejiang')
                    ->formatStateUsing(fn ($state): ?string => self::$yuejiang[$state]),
                Tables\Columns\TextColumn::make('jiuzongmen')
                    ->formatStateUsing(fn ($state): ?string => self::$jiuzongmen[$state]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPans::route('/'),
            'create' => Pages\CreatePan::route('/create'),
            'edit' => Pages\EditPan::route('/{record}/edit'),
        ];
    }

    /**
     * 月将加时，得出天盘结果
     *
     *
     * @param  tinyint  $yuejiang  月将
     * @param  DateTime  $shichenTime  时间
     * @param  tinyint  $tiangan  天盘索引，第几个天盘
     * @return array
     */
    public static function yuejiangJiashi($yuejiang, $shichenTime, $tianpanIndex)
    {
        $shichen = self::time2Shichen($shichenTime);
        $yueJiangJiaShi = $yuejiang - $shichen;
        if ($yueJiangJiaShi < 0) {
            $yueJiangJiaShi += 12;
        }
        $return = $yueJiangJiaShi + $tianpanIndex;
        if ($return > 11) {
            $return -= 12;
        }

        return $return;
    }

    /**
     * 通过时间计算出时辰
     *
     *
     * @param  DateTime  $shichenTime  时间
     * @return array
     */
    public static function time2Shichen($shichenTime)
    {
        $hour = date('G', strtotime($shichenTime));
        $shichen = self::$hour2Shichen[$hour];

        return $shichen;
    }

    /**
     * 获取月将
     *
     *
     * @param  array  $datetime  一个包含了年月日时分秒六个元素的数组
     * @return array
     */
    public static function getYuejiang($datetime)
    {
        // 获取节气index
        $solar = SolarTime::fromYmdHms($datetime[0], $datetime[1], $datetime[2], $datetime[3], $datetime[4], $datetime[5]);
        $solarIndex = $solar->getTerm()->getIndex();

        // 通过节气算出月将
        return self::$jieqi2Yuejiang[$solarIndex];
    }

    /**
     * 把字符串类型的时间转换为一个包括年月日时分秒的数组
     *
     *
     * @param  DateTime  $datetime  时间
     * @return array
     */
    public static function time2array($datetime)
    {
        $time = strtotime($datetime);
        $y = date('Y', $time);
        $m = date('m', $time);
        $d = date('d', $time);
        $h = date('H', $time);
        $i = date('i', $time);
        $s = date('s', $time);

        return [$y, $m, $d, $h, $i, $s];
    }

    /**
     * 获取两个五行的生克关系
     *
     *
     * @param  tinyint  $upIndex  上的五行index
     * @param  tinyint  $downIndex  下的五行index
     * @return array
     */
    public static function getShengke($upIndex, $downIndex)
    {
        $up = Element::fromIndex($upIndex);
        $down = Element::fromIndex($downIndex);

        $woSheng = $up->getReinforce();
        $woKe = $up->getRestrain();
        $shengWo = $up->getReinforced();
        $keWo = $up->getRestrained();
        $wuxingShengke = [0, '无']; // 生克状态 0：无生克，1：上克下，2：上生下，-1：下贼上，-2：下生上
        if ($woSheng == $down) {
            $wuxingShengke = [2, '上生下'];
        } elseif ($woKe == $down) {
            $wuxingShengke = [1, '上克下'];
        } elseif ($shengWo == $down) {
            $wuxingShengke = [-2, '下生上'];
        } elseif ($keWo == $down) {
            $wuxingShengke = [-1, '下贼上'];
        }

        return $wuxingShengke;
    }

    /**
     * 起盘
     *
     *
     * @param  DateTime  $datetime  起盘的时间
     * @return array
     */
    public static function qipan($datetime)
    {

        // 第零步，清空session
        $pan = [];
        session()->put('pan', $pan);

        // 第一步，根据时间确定月将
        $time = self::time2array($datetime);
        $yuejiang = self::getYuejiang($time);
        $pan['yuejiang'] = $yuejiang;

        // 第二步，获取四柱
        $solarTime = SolarTime::fromYmdHms($time[0], $time[1], $time[2], $time[3], $time[4], $time[5]);
        $sizhu = $solarTime->getLunarHour();
        $sizhuYear = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getYear()->getIndex()];
        $sizhuMonth = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getMonth()->getIndex()];
        $sizhuDay = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getDay()->getIndex()];
        $sizhuHour = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getHour()->getIndex()];
        $pan['niangan'] = $sizhuYear[0];
        $pan['nianzhi'] = $sizhuYear[1];
        $pan['yuegan'] = $sizhuMonth[0];
        $pan['yuezhi'] = $sizhuMonth[1];
        $pan['rigan'] = $sizhuDay[0];
        $pan['rizhi'] = $sizhuDay[1];
        $pan['shigan'] = $sizhuHour[0];
        $pan['shizhi'] = $sizhuHour[1];
        $pan['sizhu'] = self::$tiangan[$sizhuYear[0]].self::$dizhi[$sizhuYear[1]].' / '
            .self::$tiangan[$sizhuMonth[0]].self::$dizhi[$sizhuMonth[1]].' / '
            .self::$tiangan[$sizhuDay[0]].self::$dizhi[$sizhuDay[1]].' / '
            .self::$tiangan[$sizhuHour[0]].self::$dizhi[$sizhuHour[1]];

        // 第三步，设置天盘数据
        $pan['tianpan'] = [
            self::yuejiangJiashi($yuejiang, $datetime, 0),
            self::yuejiangJiashi($yuejiang, $datetime, 1),
            self::yuejiangJiashi($yuejiang, $datetime, 2),
            self::yuejiangJiashi($yuejiang, $datetime, 3),
            self::yuejiangJiashi($yuejiang, $datetime, 4),
            self::yuejiangJiashi($yuejiang, $datetime, 5),
            self::yuejiangJiashi($yuejiang, $datetime, 6),
            self::yuejiangJiashi($yuejiang, $datetime, 7),
            self::yuejiangJiashi($yuejiang, $datetime, 8),
            self::yuejiangJiashi($yuejiang, $datetime, 9),
            self::yuejiangJiashi($yuejiang, $datetime, 10),
            self::yuejiangJiashi($yuejiang, $datetime, 11),
        ];

        // 第四步，设置四课数据
        $pan['sike'] = [];
        $pan['sike'][] = $pan['rigan'];
        $pan['sike'][] = $pan['tianpan'][self::$jigong[$pan['rigan']]];
        $pan['sike'][] = $pan['tianpan'][self::$jigong[$pan['rigan']]];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][2]];
        $pan['sike'][] = $pan['rizhi'];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][4]];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][4]];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][6]];

        // 四课去重之后的数据（包含生克关系）
        $sikeUnique = [];
        if (! array_search($pan['sike'][1].self::$wuxingTian[$pan['sike'][0]], $sikeUnique)) {
            $sikeUnique[1] = $pan['sike'][1].self::$wuxingTian[$pan['sike'][0]];
        }
        if (! array_search($pan['sike'][3].self::$wuxingDi[$pan['sike'][2]], $sikeUnique)) {
            $sikeUnique[3] = $pan['sike'][3].self::$wuxingDi[$pan['sike'][2]];
        }
        if (! array_search($pan['sike'][5].self::$wuxingDi[$pan['sike'][4]], $sikeUnique)) {
            $sikeUnique[5] = $pan['sike'][5].self::$wuxingDi[$pan['sike'][4]];
        }
        if (! array_search($pan['sike'][7].self::$wuxingDi[$pan['sike'][6]], $sikeUnique)) {
            $sikeUnique[7] = $pan['sike'][7].self::$wuxingDi[$pan['sike'][6]];
        }

        // 四课的五行生克关系
        $jiuZongMen = 0; // 九宗门 未知
        $shangKeXiaIndex = [];  // 记录上克下的四课index
        $xiaZeiShangIndex = []; // 记录下贼上的四课index
        $pan['wuxingShengke0'] = self::getShengke(self::$wuxingDi[$pan['sike'][1]], self::$wuxingTian[$pan['sike'][0]]);
        if ($pan['wuxingShengke0'][0] == 1) { // 上克下
            $shangKeXiaIndex[] = 1;
        }
        if ($pan['wuxingShengke0'][0] == -1) { // 下贼上
            $xiaZeiShangIndex[] = 1;
        }
        $pan['wuxingShengke1'] = self::getShengke(self::$wuxingDi[$pan['sike'][3]], self::$wuxingDi[$pan['sike'][2]]);
        if ($pan['wuxingShengke1'][0] == 1 && isset($sikeUnique[3])) { // 上克下
            $shangKeXiaIndex[] = 3;
        }
        if ($pan['wuxingShengke1'][0] == -1 && isset($sikeUnique[3])) { // 下贼上
            $xiaZeiShangIndex[] = 3;
        }
        $pan['wuxingShengke2'] = self::getShengke(self::$wuxingDi[$pan['sike'][5]], self::$wuxingDi[$pan['sike'][4]]);
        if ($pan['wuxingShengke2'][0] == 1 && isset($sikeUnique[5])) { // 上克下
            $shangKeXiaIndex[] = 5;
        }
        if ($pan['wuxingShengke2'][0] == -1 && isset($sikeUnique[5])) { // 下贼上
            $xiaZeiShangIndex[] = 5;
        }
        $pan['wuxingShengke3'] = self::getShengke(self::$wuxingDi[$pan['sike'][7]], self::$wuxingDi[$pan['sike'][6]]);
        if ($pan['wuxingShengke3'][0] == 1 && isset($sikeUnique[7])) { // 上克下
            $shangKeXiaIndex[] = 7;
        }
        if ($pan['wuxingShengke3'][0] == -1 && isset($sikeUnique[7])) { // 下贼上
            $xiaZeiShangIndex[] = 7;
        }

        // 第五步，设置三传数据
        $pan['sanchuan0'] = 0;
        $pan['sanchuan1'] = 0;
        $pan['sanchuan2'] = 0;
        // 九宗门：重审或元首
        if (count($xiaZeiShangIndex) == 1 || (count($xiaZeiShangIndex) == 0 && count($shangKeXiaIndex) == 1)) {
            if (count($xiaZeiShangIndex) == 1) {
                $pan['sanchuan0'] = $pan['sike'][$xiaZeiShangIndex[0]];
                $jiuZongMen = 2; // 重审
            } else {
                $pan['sanchuan0'] = $pan['sike'][$shangKeXiaIndex[0]];
                $jiuZongMen = 1; // 元首
            }
        }
        // 九宗门：比用或知一
        $riganXiangbi = []; // 与日干相比的四课index
        $riganBubi = []; // 与日干不相比的四课index
        if (count($xiaZeiShangIndex) > 1) {
            $jiuZongMen = 3; // 比用
            $riganYinyang = self::$yinyangTian[$pan['rigan']]; // 日干阴阳
            foreach ($xiaZeiShangIndex as $v) {
                if (self::$yinyangDi[$pan['sike'][$v]] == $riganYinyang) {
                    $riganXiangbi[] = $v;
                } else {
                    $riganBubi[] = $v;
                }
            }
            if (count($riganXiangbi) == 1 || (count($riganXiangbi) > 1 && $pan['sike'][$riganXiangbi[0]] == $pan['sike'][$riganXiangbi[1]])) { // 四课有重复的，也按比用算
                $pan['sanchuan0'] = $pan['sike'][$riganXiangbi[0]];
            }
        }
        if (count($shangKeXiaIndex) > 1 && count($xiaZeiShangIndex) == 0) {
            $jiuZongMen = 4; // 知一
            $riganYinyang = self::$yinyangTian[$pan['rigan']]; // 日干阴阳
            foreach ($shangKeXiaIndex as $v) {
                if (self::$yinyangDi[$pan['sike'][$v]] == $riganYinyang) {
                    $riganXiangbi[] = $v;
                } else {
                    $riganBubi[] = $v;
                }
            }
            if (count($riganXiangbi) == 1 || (count($riganXiangbi) > 1 && $pan['sike'][$riganXiangbi[0]] == $pan['sike'][$riganXiangbi[1]])) { // 四课有重复的，也按比用算
                $pan['sanchuan0'] = $pan['sike'][$riganXiangbi[0]];
            }
        }
        // 九宗门：涉害
        $xianghai = []; // 相害数据，key=>四课index，value：相害数
        if ((count($xiaZeiShangIndex) > 1 || (count($shangKeXiaIndex) > 1 && count($xiaZeiShangIndex) == 0)) && ((count($riganXiangbi) > 1 && $pan['sike'][$riganXiangbi[0]] != $pan['sike'][$riganXiangbi[1]]) || count($riganXiangbi) == 0)) { // 与日干俱比或俱不比
            $jiuZongMen = 5; // 涉害
            if (count($riganXiangbi) > 1) { // 如果与日干俱不比，则使用俱不比的数组判断涉害数
                $biyongArr = $riganXiangbi;
            } else {
                $biyongArr = $riganBubi;
            }
            // 轮询
            $sikeXia = []; // 用于和四孟四仲求交集的四课下课数组
            foreach ($biyongArr as $v) {
                if ($v - 1 == 0) { // 如果是天干位置，则应通过寄宫查地支
                    $sikeXia[] = self::$jigong[$pan['sike'][$v - 1]];
                } else {
                    $sikeXia[] = $pan['sike'][$v - 1];
                }
                $shehaiBegin = array_search($pan['sike'][$v], $pan['tianpan']);
                $shehaiEnd = $pan['sike'][$v];

                $shehaiArr = []; // 创建一个数组，是此一课中上方的值在地盘上的index，一直到这个值在地盘上的本位
                if ($shehaiBegin <= $shehaiEnd) {
                    $shehaiArr = range($shehaiBegin, $shehaiEnd);
                } else {
                    $shehaiArr = array_merge(range($shehaiBegin, 11), range(0, $shehaiEnd));
                }
                $xianghai[$v] = 0;
                foreach ($shehaiArr as $w) { // 涉害过程轮询
                    if (count($xiaZeiShangIndex) > 1 && self::getShengke(self::$wuxingDi[$w], self::$wuxingDi[$pan['sike'][$v]])[0] == 1) { // 看地盘位的值是否相害
                        $xianghai[$v]++; // 下贼上时，获取生克关系也应取下贼上
                        // var_dump('地盘 ' . self::$dizhi[$w] . ' 贼 ' . self::$dizhi[$pan['sike'][$v]]);
                    } elseif (count($shangKeXiaIndex) > 1 && self::getShengke(self::$wuxingDi[$pan['sike'][$v]], self::$wuxingDi[$w])[0] == 1) { // 看地盘位的值是否相害
                        $xianghai[$v]++; // 上克下时，获取生克关系也应取上克下
                        // var_dump('地盘 ' . self::$dizhi[$pan['sike'][$v]] . ' 克 ' . self::$dizhi[$w]);
                    }
                    // 看地盘所寄宫的天干的五行是否与这一课相害
                    $jigongTiangan = array_keys(self::$jigong, $w);
                    foreach ($jigongTiangan as $x) {
                        if (count($xiaZeiShangIndex) > 1 && self::getShengke(self::$wuxingTian[$x], self::$wuxingDi[$pan['sike'][$v]])[0] == 1) {
                            $xianghai[$v]++;
                            // var_dump('天干 ' . self::$tiangan[$x] . ' 贼 ' . self::$dizhi[$pan['sike'][$v]]);
                        } elseif (count($shangKeXiaIndex) > 1 && self::getShengke(self::$wuxingDi[$pan['sike'][$v]], self::$wuxingTian[$x])[0] == 1) {
                            $xianghai[$v]++;
                            // var_dump('天干 ' . self::$dizhi[$pan['sike'][$v]] . ' 克 ' . self::$tiangan[$x]);
                        }
                    }
                }
            }
            // 取涉害多者为初传
            if (count($xianghai) > 0) {
                $shehaiMax = array_search(max($xianghai), $xianghai); // 涉害多者在四课中的index
                $pan['sanchuan0'] = $pan['sike'][$shehaiMax];
            }
            if ((count($xianghai) > 1 || count($xianghai) == 0) && count(array_unique($xianghai)) == 1) { // 涉害数相等
                // 依次取孟仲季
                $meng = [2, 5, 8, 11]; // 四孟
                $zhong = [0, 3, 6, 9]; // 四仲
                $ifMeng = array_intersect($sikeXia, $meng);
                $ifZhong = array_intersect($sikeXia, $zhong);
                if (! empty($ifMeng)) {
                    $jiuZongMen = 6; // 涉害见机
                    $mengDipan = array_shift($ifMeng);
                    $pan['sanchuan0'] = $pan['tianpan'][$mengDipan];
                } elseif (! empty($ifZhong)) {
                    $jiuZongMen = 7; // 涉害察微
                    $zhongDipan = array_shift($ifZhong);
                    $pan['sanchuan0'] = $pan['tianpan'][$zhongDipan];
                } else {
                    $jiuZongMen = 8; // 涉害缀瑕
                    if (self::$yinyangTian[$pan['rigan']] == 1) { // 阳日取干上神
                        $pan['sanchuan0'] = $pan['sike'][1];
                    } else {                                    // 阴日取支上神
                        $pan['sanchuan0'] = $pan['sike'][5];
                    }
                }
            }
        }
        $pan['sanchuan1'] = $pan['tianpan'][$pan['sanchuan0']];
        $pan['sanchuan2'] = $pan['tianpan'][$pan['sanchuan1']];

        if ($pan['tianpan'][0] == 6 && ! in_array($pan['rigan'].$pan['rizhi'], ['37', '57'])) {
            // 除丁未、己未，均为反吟
            $jiuZongMen = 20; // 反吟无依
            $pan['sanchuan1'] = self::$chong[$pan['sanchuan0']];
            $pan['sanchuan2'] = self::$chong[$pan['sanchuan1']];
            if (($pan['rigan'] == 7 && $pan['rizhi'] == 7) || ($pan['rigan'] == 7 && $pan['rizhi'] == 1) || ($pan['rigan'] == 3 && $pan['rizhi'] == 1) || ($pan['rigan'] == 5 && $pan['rizhi'] == 1)) {
                // 辛未，辛丑，丁丑，己丑 无亲格
                $jiuZongMen = 21; // 反吟无亲
                if ($pan['rizhi'] == 1) {
                    $pan['sanchuan0'] = 11;
                } else {
                    $pan['sanchuan0'] = 5;
                }
                $pan['sanchuan1'] = $pan['sike'][5];
                $pan['sanchuan2'] = $pan['sike'][1];
            }
        } else {
            // 九宗门 遥克
            if (count($xiaZeiShangIndex) == 0 && count($shangKeXiaIndex) == 0 && ($pan['sike'][1] != $pan['sike'][5] || $pan['sike'][3] != $pan['sike'][7])) {
                $yaokeShangKeXia = [];
                $yaokeXiaZeiShang = [];
                foreach ($sikeUnique as $k => $v) { // 只对二三四课中去重后的数据做判断
                    if (self::getShengke(self::$wuxingDi[$pan['sike'][$k]], self::$wuxingTian[$pan['rigan']])[0] == 1) {
                        $yaokeShangKeXia[] = $k;
                    }
                    if (self::getShengke(self::$wuxingTian[$pan['rigan']], self::$wuxingDi[$pan['sike'][$k]])[0] == 1) {
                        $yaokeXiaZeiShang[] = $k;
                    }
                }
                if (count($yaokeShangKeXia) > 0) {
                    $jiuZongMen = 9; // 遥克蒿矢
                    if (count($yaokeShangKeXia) == 1) {
                        $pan['sanchuan0'] = $pan['sike'][$yaokeShangKeXia[0]];
                    } else {
                        foreach ($yaokeShangKeXia as $a) {
                            if (self::$yinyangTian[$pan['rigan']] == self::$yinyangDi[$pan['sike'][$a]]) {
                                $pan['sanchuan0'] = $pan['sike'][$a];
                                break;
                            }
                        }
                    }
                    $pan['sanchuan1'] = $pan['tianpan'][$pan['sanchuan0']];
                    $pan['sanchuan2'] = $pan['tianpan'][$pan['sanchuan1']];
                } elseif (count($yaokeXiaZeiShang) > 0) {
                    $jiuZongMen = 10; // 遥克弹射
                    if (count($yaokeXiaZeiShang) == 1) {
                        $pan['sanchuan0'] = $pan['sike'][$yaokeXiaZeiShang[0]];
                    } else {
                        foreach ($yaokeXiaZeiShang as $a) {
                            if (self::$yinyangTian[$pan['rigan']] == self::$yinyangDi[$pan['sike'][$a]]) {
                                $pan['sanchuan0'] = $pan['sike'][$a];
                                break;
                            }
                        }
                    }
                    $pan['sanchuan1'] = $pan['tianpan'][$pan['sanchuan0']];
                    $pan['sanchuan2'] = $pan['tianpan'][$pan['sanchuan1']];
                } elseif (count($yaokeShangKeXia) == 0 && count($yaokeXiaZeiShang) == 0) {
                    if ($pan['sike'][1] != $pan['sike'][7] && $pan['sike'][3] != $pan['sike'][5]) { // 四课不重复者为昴星
                        if (self::$yinyangTian[$pan['rigan']] == 1) {
                            $jiuZongMen = 11; // 昴星虎视
                            $pan['sanchuan0'] = $pan['tianpan'][9]; // 初传取地盘酉上神
                            $pan['sanchuan1'] = $pan['sike'][5];  // 中传取支上神
                            $pan['sanchuan2'] = $pan['sike'][1];  // 末传取干上神
                        } else {
                            $jiuZongMen = 12; // 昴星冬蛇掩目
                            $pan['sanchuan0'] = array_search(9, $pan['tianpan']); // 初传取天盘酉下神
                            $pan['sanchuan1'] = $pan['sike'][1];                // 中传取干上神
                            $pan['sanchuan2'] = $pan['sike'][5];                // 末传取支上神

                        }
                    } elseif ($pan['sike'][1] == $pan['sike'][7] || $pan['sike'][3] == $pan['sike'][5]) { // 四课中，1和7相等，或3和5相等，判断别责
                        $jiuZongMen = 13; // 别责
                        if (self::$yinyangTian[$pan['rigan']] == 1) {
                            $ganhe = $pan['rigan'] + 5; // 日干加5就是日干的天干五合
                            if ($ganhe > 9) {
                                $ganhe = $ganhe - 10;
                            }
                            $pan['sanchuan0'] = $pan['tianpan'][self::$jigong[$ganhe]];
                        } else {
                            $zhihe = $pan['rizhi'] + 4; // 日支加4就是日支的地支三合
                            if ($zhihe > 11) {
                                $zhihe = $zhihe - 12;
                            }
                            $pan['sanchuan0'] = $zhihe;
                        }
                        $pan['sanchuan1'] = $pan['sanchuan2'] = $pan['sike'][1];
                    }
                }
            } elseif (($pan['sike'][1] == $pan['sike'][5] && $pan['sike'][3] == $pan['sike'][7]) && $pan['tianpan'][0] != 0 && count($shangKeXiaIndex) == 0 && count($xiaZeiShangIndex) == 0) { // 只有两课且天地盘不重合且没有上下课为八专
                $jiuZongMen = 14; // 八专
                if (self::$yinyangTian[$pan['rigan']] == 1) {
                    $pan['sanchuan0'] = $pan['sike'][1] + 2;
                    if ($pan['sanchuan0'] > 11) {
                        $pan['sanchuan0'] = $pan['sanchuan0'] - 12;
                    }
                } else {
                    $pan['sanchuan0'] = $pan['sike'][7] - 2;
                    if ($pan['sanchuan0'] < 0) {
                        $pan['sanchuan0'] = $pan['sanchuan0'] + 12;
                    }
                }
                $pan['sanchuan1'] = $pan['sanchuan2'] = $pan['sike'][1];
                if ($pan['sanchuan0'] == $pan['sanchuan1'] && $pan['sanchuan1'] == $pan['sanchuan2']) {
                    $jiuZongMen = 15; // 八专独足
                }
            }
            if ($pan['tianpan'][0] == 0) {
                $jiuZongMen = 16; // 伏吟不虞
                if (count($xiaZeiShangIndex) > 0) {
                    $pan['sanchuan0'] = $pan['sike'][$xiaZeiShangIndex[0]];
                } elseif (count($shangKeXiaIndex) > 0) {
                    $pan['sanchuan0'] = $pan['sike'][$shangKeXiaIndex[0]];
                }
                $pan['sanchuan1'] = self::$xing[$pan['sanchuan0']];
                $pan['sanchuan2'] = self::$xing[$pan['sanchuan1']];
                if (in_array($pan['sanchuan0'], [4, 6, 9, 11])) { // 初传自刑
                    $pan['sanchuan1'] = $pan['sike'][5];
                    $pan['sanchuan2'] = self::$xing[$pan['sanchuan1']];
                    if (in_array($pan['sanchuan1'], [4, 6, 9, 11])) { // 中传自刑
                        $pan['sanchuan2'] = self::$chong[$pan['sanchuan1']];
                    }
                }
                if (count($xiaZeiShangIndex) == 0 && count($shangKeXiaIndex) == 0) {
                    if (self::$yinyangTian[$pan['rigan']] == 1) {
                        $pan['sanchuan0'] = $pan['sike'][1];
                        $jiuZongMen = 17; // 伏吟自任
                    } else {
                        $pan['sanchuan0'] = $pan['sike'][5];
                        $jiuZongMen = 18; // 伏吟自信
                    }
                    if (in_array($pan['sanchuan0'], [4, 6, 9, 11])) { // 初传自刑
                        $jiuZongMen = 19; // 伏吟杜传
                        if (self::$yinyangTian[$pan['rigan']] == 1) {
                            $pan['sanchuan1'] = $pan['sike'][5];
                        } else {
                            $pan['sanchuan1'] = $pan['sike'][1];
                        }
                    } else {
                        $pan['sanchuan1'] = self::$xing[$pan['sanchuan0']];
                    }
                    if (in_array($pan['sanchuan1'], [4, 6, 9, 11])) { // 中传自刑
                        $pan['sanchuan2'] = self::$chong[$pan['sanchuan1']];
                    } else {
                        $pan['sanchuan2'] = self::$xing[$pan['sanchuan1']];
                    }
                }
            }
        }

        $pan['jiuzongmen'] = $jiuZongMen;

        $liuqin0 = self::getShengke(self::$wuxingDi[$pan['sanchuan0']], self::$wuxingTian[$pan['rigan']]);
        $pan['liuqin0'] = $liuqin0[0];
        $liuqin1 = self::getShengke(self::$wuxingDi[$pan['sanchuan1']], self::$wuxingTian[$pan['rigan']]);
        $pan['liuqin1'] = $liuqin1[0];
        $liuqin2 = self::getShengke(self::$wuxingDi[$pan['sanchuan2']], self::$wuxingTian[$pan['rigan']]);
        $pan['liuqin2'] = $liuqin2[0];

        // 计算旬遁空亡
        $xunIndex = intval($sizhu->getEightChar()->getDay()->getIndex() / 10); // 日柱的index除以10得知是甲x旬
        $xunFirstZhi = [0, 10, 8, 6, 4, 2]; // 甲子旬以子开头，甲戌旬以戌开头，申，午，辰，寅
        $pan['xundun0'] = $pan['sanchuan0'] - $xunFirstZhi[$xunIndex] < 0 ? $pan['sanchuan0'] - $xunFirstZhi[$xunIndex] + 12 : $pan['sanchuan0'] - $xunFirstZhi[$xunIndex]; // 计算地支在本旬内排第几个
        $pan['xundun1'] = $pan['sanchuan1'] - $xunFirstZhi[$xunIndex] < 0 ? $pan['sanchuan1'] - $xunFirstZhi[$xunIndex] + 12 : $pan['sanchuan1'] - $xunFirstZhi[$xunIndex];
        $pan['xundun2'] = $pan['sanchuan2'] - $xunFirstZhi[$xunIndex] < 0 ? $pan['sanchuan2'] - $xunFirstZhi[$xunIndex] + 12 : $pan['sanchuan2'] - $xunFirstZhi[$xunIndex];

        // 起贵人
        $tianjiang = [[1, 7], [0, 8], [11, 9], [11, 9], [1, 7], [0, 8], [1, 7], [6, 2], [5, 3], [5, 3]];
        $guiren = $tianjiang[$pan['rigan']][1]; // 夜贵
        if (in_array($pan['shizhi'], [3, 4, 5, 6, 7, 8])) { // 昼贵
            $guiren = $tianjiang[$pan['rigan']][0];
        }
        $dipanGuiren = array_search($guiren, $pan['tianpan']);
        $shunni = 1; // 贵人顺行
        if (in_array($dipanGuiren, [5, 6, 7, 8, 9, 10])) {
            $shunni = -1; // 贵人逆行
        }
        $pan['tianjiang'] = [];
        for ($i = 0; $i < 12; $i++) {
            if ($shunni == 1) {
                $pan['tianjiang'][$i] = $pan['tianpan'][0] - $guiren + $i;
            } else {
                $pan['tianjiang'][$i] = $guiren - $i - $pan['tianpan'][0];
            }
            if ($pan['tianjiang'][$i] < -12) {
                $pan['tianjiang'][$i] += 24;
            } elseif ($pan['tianjiang'][$i] < 0) {
                $pan['tianjiang'][$i] += 12;
            } elseif ($pan['tianjiang'][$i] > 11) {
                $pan['tianjiang'][$i] -= 12;
            }
        }

        // 三传的贵人
        $sanchuan0tianjiangIndex = array_search($pan['sanchuan0'], $pan['tianpan']);
        $pan['sanchuan0tianjiang'] = $pan['tianjiang'][$sanchuan0tianjiangIndex];
        $sanchuan1tianjiangIndex = array_search($pan['sanchuan1'], $pan['tianpan']);
        $pan['sanchuan1tianjiang'] = $pan['tianjiang'][$sanchuan1tianjiangIndex];
        $sanchuan2tianjiangIndex = array_search($pan['sanchuan2'], $pan['tianpan']);
        $pan['sanchuan2tianjiang'] = $pan['tianjiang'][$sanchuan2tianjiangIndex];

        // 最后一步，起盘数据写session
        session()->put('pan', $pan);
    }
}
