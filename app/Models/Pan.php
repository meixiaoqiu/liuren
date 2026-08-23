<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pan extends Model
{
    use HasFactory;

    /**
     * 与模型关联的表。
     *
     * @var string
     */
    protected $table = 'lr_pan';

    protected $fillable = ['shichen', 'sizhu', 'yuejiang', 'niangan', 'nianzhi', 'yuegan', 'yuezhi', 'rigan', 'rizhi', 'shigan', 'shizhi', 'tianpan0', 'tianpan1', 'tianpan2', 'tianpan3', 'tianpan4', 'tianpan5', 'tianpan6', 'tianpan7', 'tianpan8', 'tianpan9', 'tianpan10', 'tianpan11', 'sike0', 'sike1', 'sike2', 'sike3', 'sike4', 'sike5', 'sike6', 'sike7', 'sanchuan0', 'sanchuan1', 'sanchuan2', 'tianjiang0', 'tianjiang1', 'tianjiang2', 'tianjiang3', 'tianjiang4', 'tianjiang5', 'tianjiang6', 'tianjiang7', 'tianjiang8', 'tianjiang9', 'tianjiang10', 'tianjiang11', 'xundun0', 'xundun1', 'xundun2', 'liuqin0', 'liuqin1', 'liuqin2', 'sanchuan0tianjiang', 'sanchuan1tianjiang', 'sanchuan2tianjiang', 'jiuzongmen', 'xingnian', 'nianming', 'explain'];
}
