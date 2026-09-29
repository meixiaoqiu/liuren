<?php

namespace App\Domain\Astronomy;

use DateTimeInterface;

/** 文件作用：抽象“给定瞬间取得月宿十二宫”的事实来源，便于生产查表与古籍结构测试解耦。 */
interface MoonPalaceLookup
{
    public function palaceAt(DateTimeInterface $time): int;
}
