//六十花甲算法
var _firstYear=4; //第一个甲子年为公元4年
var _earth=[0,1,2,3,4,5,6,7,8,9,10,11]; //十二地支
var _earthStr=["子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥"];
var _sky=[0,1,2,3,4,5,6,7,8,9]; //十天干
var _skyStr=["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];
var _solarTerm=["春分","清明","谷雨","立夏","小满","芒种","夏至","小暑","大暑","立秋","处暑","白露","秋分","寒露","霜降","立冬","小雪","大雪","冬至","小寒","大寒","立春","雨水","惊蛰"];
var _monthGeneral=[1,0,11,10,9,8,7,6,5,4,3,2,1,0];//月将，从冬至之后第一个月将开始。因为算出的中
var _monthGeneralStr=["丑","子","亥","戌","酉","申","未","午","巳","辰","卯","寅","丑","子"];//月将，从冬至之后第一个月将开始。因为算出的中气数组可能有14个，所以月将也为14个

//根据年份参数返回60花甲年
function huaJia(year){
	var sixty=(year-_firstYear)%60; //当前六十花甲序数
	var sky=sixty%10; //当前天干
	var earth=sixty%12; //当前地支
	//console.log(_skyStr[sky],_earthStr[earth]);
	return [sky,earth];
}

//根据父级年干获得子级正月（立春至惊蛰前一日）月干
function getYueXun(parent){
	switch (parent) {
		//甲，己年 丙寅月开始
		case 0:
		case 5:
			return [2,3,4,5,6,7,8,9,0,1,2,3];
			break;
		
		//乙或庚年 戊寅月	
		case 1:
		case 6:
			return [4,5,6,7,8,9,0,1,2,3,4,5];
			break;
		
		//丙或辛年 庚寅月
		case 2:
		case 7:
			return [6,7,8,9,0,1,2,3,4,5,6,7];
			break;
			
		//丁或壬年 壬寅月
		case 3:
		case 8:
			return [8,9,0,1,2,3,4,5,6,7,8,9];
			break;
			
		//戊或癸年 甲寅月
		case 4:
		case 9:
			return [0,1,2,3,4,5,6,7,8,9,0,1];
			break;
	}
}

//根据父级日干获得子级时干
function getShiXun(parent){
	switch (parent) {
		//甲或己日 子时
		case 0:
		case 5:
			return [0,1,2,3,4,5,6,7,8,9,0,1];
			break;
		
		//乙或庚日 子时	
		case 1:
		case 6:
			return [2,3,4,5,6,7,8,9,0,1,2,3];
			break;
		
		//丙或辛日 子时
		case 2:
		case 7:
			return [4,5,6,7,8,9,0,1,2,3,4,5];
			break;
			
		//丁或壬日 子时
		case 3:
		case 8:
			return [6,7,8,9,0,1,2,3,4,5,6,7];
			break;
			
		//戊或癸日 子时
		case 4:
		case 9:
			return [8,9,0,1,2,3,4,5,6,7,8,9];
			break;
	}
}

//根据年月日时分秒起课
function qiKe(y,m,d,h,min,s){
	var zq=new Array();  //当年所有的中气
	var jq=new Array();  //当年所有的节气
	//从冬至开始,连续计算14个中气时刻
	var i,t1=365.2422*(y-2000)-50; //农历年首始于前一年的冬至,为了节气中气一起算,取前年大雪之前
	for(i=0;i<14;i++){   //计算节气(从冬至开始),注意:返回的是力学时
	  zq[i]=jiaoCal(t1+i*30.4,i*30-90, 0)+J2000+8/24; //中气的儒略日计算,冬至的太阳黄经是270度(或-90度) 用于起月将
	  jq[i]=jiaoCal(t1+i*30.4,i*30-105,0)+J2000+8/24; //节气的儒略日计算 用于更换年旬和月旬
	}
	
	//根据参数计算儒略日
	JDate.setFromStr(y+""+m+""+d+" "+h+""+min+""+s);
	var jd=JDate.toJD();
	
	//算出月将
	var yueJiang;
	for(i=0;i<zq.length;i++){
		if(jd<zq[i]){
			yueJiang=_monthGeneral[i-1];
			break;
		}
	}
	
		//判断年旬 当日期大于立春，用当年的旬，小于立春用去年的旬
		var nianXun;
		if(jd<jq[2]){
			nianXun=huaJia(y-1);
		}else{
			nianXun=huaJia(y);
		}
		
		//算出月旬
		var yueXun;
		for(i=0;i<jq.length;i++){
			if(jd<jq[i]){
				var yueXunSkyArr=getYueXun(nianXun[0]);
				var j=i-1;
				var temp=yueXunSkyArr[j];
				yueXun=[temp,j];
				break;
			}
		}
		
	//算出日旬
	JDate.setFromStr("19010214 230000"); //已知1901年2月15日为甲子日
	var jdJiaZi=JDate.toJD();
	var riXunIndex=(jd-jdJiaZi)%60;
	var riXun=[];
	//判断日干
	if(riXunIndex>=10){
		riXun[0]=riXunIndex%10;
	}else{
		riXun[0]=riXunIndex;
	}
	riXun[0]=parseInt(riXun[0]);
	//判断日支
	if(riXunIndex>=12){
		riXun[1]=riXunIndex%12;
	}else{
		riXun[1]=riXunIndex;
	}
	riXun[1]=parseInt(riXun[1]);
	//如果时间为23点到0点，是夜子时，用后一天的日旬
	if(h>=23){
		if(riXun[0]<9){
			riXun[0]=riXun[0]+1;
		}else{
			riXun[0]=0;
		}
		if(riXun[1]<11){
			riXun[1]=riXun[1]+1;
		}else{
			riXun[1]=0;
		}
	}
	
	//算出时旬
	var shiXun=[];
	//子时
  	if(h>=23 || h<1){
  		shiXun[1]=0;
  	}
  	//丑
  	if(h>=1 && h<3){
  		shiXun[1]=1;
  	}
  	//寅
  	if(h>=3 && h<5){
  		shiXun[1]=2;
  	}
  	//卯
  	if(h>=5 && h<7){
  		shiXun[1]=3;
  	}
  	//辰
  	if(h>=7 && h<9){
  		shiXun[1]=4;
  	}
  	//巳
  	if(h>=9 && h<11){
  		shiXun[1]=5;
  	}
  	//午
  	if(h>=11 && h<13){
  		shiXun[1]=6;
  	}
  	//未
  	if(h>=13 && h<15){
  		shiXun[1]=7;
  	}
  	//申
  	if(h>=15 && h<17){
  		shiXun[1]=8;
  	}
  	//酉
  	if(h>=17 && h<19){
  		shiXun[1]=9;
  	}
  	//戌
  	if(h>=19 && h<21){
  		shiXun[1]=10;
  	}
  	//亥
  	if(h>=21 && h<23){
  		shiXun[1]=11;
  	}
  	//时干
  	var shiGanArr=getShiXun(riXun[0]);
  	shiXun[0]=shiGanArr[shiXun[1]];
  	//console.log(nianXun,yueXun,riXun,shiXun,yueJiang);
  	return [nianXun[0],nianXun[1],yueXun[0],yueXun[1],riXun[0],riXun[1],shiXun[0],shiXun[1],yueJiang];
}


