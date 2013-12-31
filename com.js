//六十花甲算法
var _firstYear=4; //第一个甲子年为公元4年
var _earth=[0,1,2,3,4,5,6,7,8,9,10,11]; //十二地支
var _earthStr=["子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥"];
var _sky=[0,1,2,3,4,5,6,7,8,9]; //十天干
var _skyStr=["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];

//根据年份参数返回60花甲年
function huaJia(year){
	var sixty=(year-_firstYear)%60; //当前六十花甲序数
	var sky=sixty%10; //当前天干
	var earth=sixty%12; //当前地支
	console.log(_skyStr[sky],_earthStr[earth]);
	return [sky,earth];
}

//
 
function computes(year,month,day) 　　
{
    var y = eval(year);
    var m = eval(month);
    var d = eval(day);
 
 
    var extra = 100.0*y + m - 190002.5;
    var rjd = 367.0*y;
    rjd -= Math.floor(7.0*(y+Math.floor((m+9.0)/12.0))/4.0);
    rjd += Math.floor(275.0*m/9.0);
    rjd += d;
 
    rjd += 1721013.5;
    rjd -= 0.5*extra/Math.abs(extra);
    rjd += 0.5;
    return rjd;
}