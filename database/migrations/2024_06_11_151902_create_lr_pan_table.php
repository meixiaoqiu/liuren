<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lr_pan', function (Blueprint $table) {
            $table->id();
            $table->dateTime('shichen', precision: 0)->comment('起盘的时间');
            $table->unsignedBigInteger('sizhu')->comment('四柱');

            $table->tinyInteger('yuejiang')->comment('月将');

            $table->tinyInteger('niangan')->comment('年干');
            $table->tinyInteger('nianzhi')->comment('年支');
            $table->tinyInteger('yuegan')->comment('月干');
            $table->tinyInteger('yuezhi')->comment('月支');
            $table->tinyInteger('rigan')->comment('日干(也是四课1)');
            $table->tinyInteger('rizhi')->comment('日支');
            $table->tinyInteger('shigan')->comment('时干');
            $table->tinyInteger('shizhi')->comment('时支');

            $table->tinyInteger('tianpan0')->comment('天盘0');
            $table->tinyInteger('tianpan1')->comment('天盘1');
            $table->tinyInteger('tianpan2')->comment('天盘2');
            $table->tinyInteger('tianpan3')->comment('天盘3');
            $table->tinyInteger('tianpan4')->comment('天盘4');
            $table->tinyInteger('tianpan5')->comment('天盘5');
            $table->tinyInteger('tianpan6')->comment('天盘6');
            $table->tinyInteger('tianpan7')->comment('天盘7');
            $table->tinyInteger('tianpan8')->comment('天盘8');
            $table->tinyInteger('tianpan9')->comment('天盘9');
            $table->tinyInteger('tianpan10')->comment('天盘10');
            $table->tinyInteger('tianpan11')->comment('天盘11');

            $table->tinyInteger('sike0')->comment('四课0');
            $table->tinyInteger('sike1')->comment('四课1');
            $table->tinyInteger('sike2')->comment('四课2');
            $table->tinyInteger('sike3')->comment('四课3');
            $table->tinyInteger('sike4')->comment('四课4');
            $table->tinyInteger('sike5')->comment('四课5');
            $table->tinyInteger('sike6')->comment('四课6');
            $table->tinyInteger('sike7')->comment('四课7');

            $table->tinyInteger('sanchuan0')->comment('初传');
            $table->tinyInteger('sanchuan1')->comment('中传');
            $table->tinyInteger('sanchuan2')->comment('末传');

            $table->tinyInteger('tianjiang0')->comment('天将0');
            $table->tinyInteger('tianjiang1')->comment('天将1');
            $table->tinyInteger('tianjiang2')->comment('天将2');
            $table->tinyInteger('tianjiang3')->comment('天将3');
            $table->tinyInteger('tianjiang4')->comment('天将4');
            $table->tinyInteger('tianjiang5')->comment('天将5');
            $table->tinyInteger('tianjiang6')->comment('天将6');
            $table->tinyInteger('tianjiang7')->comment('天将7');
            $table->tinyInteger('tianjiang8')->comment('天将8');
            $table->tinyInteger('tianjiang9')->comment('天将9');
            $table->tinyInteger('tianjiang10')->comment('天将10');
            $table->tinyInteger('tianjiang11')->comment('天将11');

            $table->tinyInteger('xundun0')->comment('初传旬遁');
            $table->tinyInteger('xundun1')->comment('中传旬遁');
            $table->tinyInteger('xundun2')->comment('末传旬遁');

            $table->tinyInteger('liuqin0')->comment('初传六亲');
            $table->tinyInteger('liuqin1')->comment('中传六亲');
            $table->tinyInteger('liuqin2')->comment('末传六亲');

            $table->tinyInteger('sanchuan0tianjiang')->comment('初传天将');
            $table->tinyInteger('sanchuan1tianjiang')->comment('中传天将');
            $table->tinyInteger('sanchuan2tianjiang')->comment('末传天将');

            $table->tinyInteger('jiuzongmen')->comment('九宗门');
            $table->tinyInteger('xingnian')->comment('行年')->default(0);
            $table->tinyInteger('nianming')->comment('年命')->default(0);
            $table->text('explain')->comment('解盘');
            $table->timestamps();
            $table->unique('sizhu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lr_pan');
    }
};
