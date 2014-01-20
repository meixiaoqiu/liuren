//六十花甲算法
var _firstYear=4; //第一个甲子年为公元4年
var _earth=[0,1,2,3,4,5,6,7,8,9,10,11]; //十二地支
var _earthStr=["子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥"];
var _sky=[0,1,2,3,4,5,6,7,8,9]; //十天干
var _skyStr=["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];
var _solarTerm=["春分","清明","谷雨","立夏","小满","芒种","夏至","小暑","大暑","立秋","处暑","白露","秋分","寒露","霜降","立冬","小雪","大雪","冬至","小寒","大寒","立春","雨水","惊蛰"];
var _monthGeneral=[1,0,11,10,9,8,7,6,5,4,3,2,1,0];//月将，从冬至之后第一个月将开始。因为算出的中
var _monthGeneralStr=["丑","子","亥","戌","酉","申","未","午","巳","辰","卯","寅","丑","子"];//月将，从冬至之后第一个月将开始。因为算出的中气数组可能有14个，所以月将也为14个
var _jiGong=[2,4,5,7,5,7,8,10,11,1]; //十干寄宫
var _jiGongZhi=[-1];
var _five=[0,1,2,3,4];
var _fiveStr=["金","木","土","水","火"];
var _earthFive=[3,2,1,1,2,4,4,2,0,0,2,3]; //地支五行
var _skyFive=[1,1,4,4,2,2,0,0,3,3]; //天干五行
var _earthYinYang=[1,0,1,0,1,0,1,0,1,0,1,0]; //地支阴阳 0阴 1阳
var _skyYinYang=[1,0,1,0,1,0,1,0,1,0]; //天干阴阳
var _siMeng=[2,5,8,11]; //四孟 寅申巳亥
var _siZhong=[0,3,6,9]; //四仲 子午卯酉
var _siJi=[1,4,7,10]; //四季 辰戌丑未

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

//十干寄宫
function jiGong(gan){
	switch(gan){
		case 0:
			return 2;
			break;
		
		case 1:
			return 4;
			break;
		
		case 2:
		case 4:
			return 5;
			break;
			
		case 3:
		case 5:
			return 7;
			break;
		
		case 6:
			return 8;
			break;
			
		case 7:
			return 10;
			break;
			
		case 8:
			return 11;
			break;
		
		case 9:
			return 1;
			break;
	}
}

//生克关系
function shengKe(a,b){
	//金克木，木克土，土克水，水克火，火克金
	if( (a==0 && b==1) || (a==1&&b==2) || (a==2&&b==3) || (a==3&&b==4) || (a==4&&b==0) ){
		return 1;
	}
	//反克
	if( (a==1&&b==0) || (a==2&&b==1) || (a==3&&b==2) || (a==4&&b==3) || (a==0&&b==4) ){
		return -1;
	}
	
	//生
	if( (a==0&&b==3) || (a==3&&b==1) || (a==1&&b==4) || (a==4&&b==2) || (a==2&&b==0) ){
		return 2;
	}
	//反生
	if( (a==3&&b==0) || (a==1&&b==3) || (a==4&&b==1) || (a==2&&b==4) || (a==0&&b==2) ){
		return -2;
	}else{
		return 0;
	}
}

/*
//由干支得出五行属性
function ganzhiToWuxing(type,value){
	
}
*/

//根据年月日时分秒起时
function qiShi(y,m,d,h,min,s){
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

//月将加时排天地盘
function tianDiPan(shiZhi,yueJiang){
	var tianPan=[];
	var tianPanFirst;
	if(shiZhi<=yueJiang){
		tianPanFirst=yueJiang-shiZhi;
	}else{
		tianPanFirst=yueJiang+12-shiZhi;
	}
	//循环12次排出天盘
	var j=0;
	for(i=0;i<=11;i++){
		tianPan[i]=tianPanFirst+i;
		if(tianPan[i]>11){
			tianPan[i]=j;
			j++;
		}
	}
	return tianPan;
}

//排四课
function siKe(riGan,riZhi,tianPan){
	//第一课
	var ke1=[];
	ke1[0]=riGan;
	riGanJiGong=jiGong(riGan);
	ke1[1]=tianPan[riGanJiGong];
	
	//第二课
	var ke2=[];
	ke2[0]=ke1[1];
	ke2[1]=tianPan[ke2[0]];
	
	//第三课
	var ke3=[];
	ke3[0]=riZhi;
	ke3[1]=tianPan[ke3[0]];
	
	//第四课
	var ke4=[];
	ke4[0]=ke3[1];
	ke4[1]=tianPan[ke4[0]];
	return [ke1[0],ke1[1],ke2[0],ke2[1],ke3[0],ke3[1],ke4[0],ke4[1]];
}

//三传
function sanChuan(siKe,tianPan){
	//初，中，末
	var chuChuan;
	var zhongChuan;
	var moChuan;
	
	//循环四课的八个值，得出五行值
	var siKeWuXing=[];
	for(i=0;i<siKe.length;i++){
		if(i==0){
			siKeWuXing[i]=_skyFive[siKe[i]];
		}else{
			siKeWuXing[i]=_earthFive[siKe[i]];
		}
	}
	
	//四课的五行生克关系
	var siKeShengKe=[];
	var j=0;
	for(i=0;i<siKeWuXing.length;i++){
		if((i+2)%2==0){
			siKeShengKe[j]=shengKe(siKeWuXing[i],siKeWuXing[i+1]);
			j++;
		}
	}
	
	var keNum=0; //上克下数量
	var keArr=[]; //上克下索引
	var zeiNum=0; //下贼上数量
	var zeiArr=[]; //下贼上索引
	for(i=0;i<siKeShengKe.length;i++) {
		if(siKeShengKe[i]==-1){
			keNum++;
			keArr.push(i);
		}
		if(siKeShengKe[i]==1){
			zeiNum++;
			zeiArr.push(i);
		}
	}
	
	//始入 or 重审
	if(zeiNum==1){
		for(i=0;i<=siKeShengKe.length;i++){
			if(siKeShengKe[i]==1){
				var index=(i+1)*2-1;
				chuChuan=siKe[index];
				zhongChuan=tianPan[chuChuan];
				moChuan=tianPan[zhongChuan];
				return [chuChuan,zhongChuan,moChuan];
			}
		}
	}
	
	//元首
	if(keNum==1 && zeiNum==0){
		for(i=0;i<=siKeShengKe.length;i++){
			if(siKeShengKe[i]==-1){
				var index=(i+1)*2-1;
				chuChuan=siKe[index];
				zhongChuan=tianPan[chuChuan];
				moChuan=tianPan[zhongChuan];
				return [chuChuan,zhongChuan,moChuan];
			}
		}
	}
	
	//比用:四课中有2或3课下贼上或2到3课上克下，则取与日干相比者为初传，两下贼上为比用，两上克下为知一
	var biYong=[]; //比用备选数组
	//判断是2下贼上还是2上克下，优先下贼上
	var zeiOrKe;
	var zeiOrKeType; //-1为上克下 1为下贼上
	if(keArr.length>1){
		zeiOrKe=keArr;
		zeiOrKeType=-1;
	}
	if(zeiArr.length>1){
		zeiOrKe=zeiArr;
		zeiOrKeType=1;
	}
	if(zeiOrKe.length>1){
		for(i=0;i<=zeiOrKe.length;i++){
			var index=(zeiOrKe[i]+1)*2-1;
			if(_earthYinYang[siKe[index]]==_skyYinYang[siKe[0]]){
				biYong.push(siKe[index]);
			}
		}
		//如果biYong数组中有一个值则按比用法起课
		if(biYong.length==1){
			chuChuan=biYong[0];
			zhongChuan=tianPan[chuChuan];
			moChuan=tianPan[zhongChuan];
			return [chuChuan,zhongChuan,moChuan];
		}
	}
	
	/*
	//涉害:四课中有两个上克下或两个下贼上，与本日日干俱比或俱不比，导致无法取传，则各就所克之处由地盘涉归本家，以克多者为发用。若其克相等，则优先地盘四孟（寅申巳亥）上者，如无孟，取四仲（子午卯酉）上者，如无仲，则不取四季（辰戌丑未），阳日取干上神发用，阴日取支上神发用
	if(biYong.length==0 || biYong.length>1){
		var sheGuiBenJia=[]; //涉归本家过程中的贼或克的次数
		for(i=0;i<=zeiOrKe.length;i++){
			var index=(zeiOrKe[i]+1)*2-1; //上神在siKe数组中的索引
			//变换地盘，将上神对应的地盘作为地盘的第一个，用于计算贼或克的次数
			var diPan=[];
			var j=0;
			for(i=0;i<=11;i++){
				//如果是第一课，则先要转换寄宫
				if(i==0 && index==1){
					diPan[i]=jiGong[zeiOrKe[index-1]];
				}else{
					diPan[i]=i+zeiOrKe[index-1];
				}
				if(diPan[i]>11){
					diPan[i]=j;
					j++;
				}
			}
			//地盘轮流与上神比较
			for(i=0;i<=diPan.length;i++){
				
			}
			switch(zeiOrKeType){
				case 1: //下贼上
					break;
					
				case -1: //上克下
					break;
			}
		}
	}
	*/
	
	//涉害：只取孟仲.
	if(biYong.length==0 || biYong.length>1){
		var xiaShen=[]; //四课下神数组
		for(i=0;i<siKe.length;i++){
			var v;
			//如果是第一课 先转为地支
			if(i==0){
				v=_jiGong[siKe[i]];
			}else{
				v=siKe[i];
			}
			
			//找四课下神
			if(i==0 || i==2 || i==4 || i==6){
				xiaShen.push(v);
			}
		}
		
		for(i=0;i<xiaShen.length;i++){
			//先找四孟
			for(j=0;j<_siMeng.length;j++){
				if(xiaShen[i]==_siMeng[j]){
					var chuChuanIndex=i*2+1;
					chuChuan=siKe[chuChuanIndex];
					zhongChuan=tianPan[chuChuan];
					moChuan=tianPan[zhongChuan];
					return [chuChuan,zhongChuan,moChuan];
				}
			}
			//再找四仲
			for(j=0;j<_siZhong.length;j++){
				if(xiaShen[i]==_siZhong[j]){
					var chuChuanIndex=i*2+1;
					chuChuan=siKe[chuChuanIndex];
					zhongChuan=tianPan[chuChuan];
					moChuan=tianPan[zhongChuan];
					return [chuChuan,zhongChuan,moChuan];
				}
			}
		}
	}
	
	//遥克
	if(keArr.length==0 && zeiArr.length==0){
		var yaoke=[];
		//找出所有遥克组合
		for(i=0;i<4;i++){
			var shangShenIndex=i+i*2+1;
			if(shengKe(siKeWuXing[0],siKeWuXing[shangShenIndex])==1){
				zeiArr.push(i);
			}
			if(shengKe(siKeWuXing[0],siKeWuXing[shangShenIndex])==-1){
				keArr.push(i);
			}
		}
	}
	//有贼优先贼
	if(zeiArr.length>0){
		if(zeiArr.length==1){
			var chuChuanIndex=zeiArr[0]+zeiArr[0]*2+1;
			chuChuan=siKe[chuChuanIndex];
			zhongChuan=tianPan[chuChuan];
			moChuan=tianPan[zhongChuan];
			return [chuChuan,zhongChuan,moChuan];
		}
		//当有两个贼时，取与日干相比者法用
		if(zeiArr.length==2){
			var chuChuanArr=[];
			for(i=0;i<2;i++){
				var chuChuanIndex=zeiArr[i]+zeiArr[i]*2+1;
				if(_skyYinYang[siKe[0]]==_earthYinYang[siKe[chuChuanIndex]]){
					chuChuanArr.push(siKe[chuChuanIndex]);
				}
			}
			if(chuChuanArr.length==1){
				chuChuan=chuChuanArr[0];
				zhongChuan=tianPan[chuChuan];
				moChuan=tianPan[zhongChuan];
				return [chuChuan,zhongChuan,moChuan];
			}
		}
	}else{
		if(keArr.length==1){
			var chuChuanIndex=keArr[0]+keArr[0]*2+1;
			chuChuan=siKe[chuChuanIndex];
			zhongChuan=tianPan[chuChuan];
			moChuan=tianPan[zhongChuan];
			return [chuChuan,zhongChuan,moChuan];
		}
		//当有两个克时，取与日干相比者法用
		if(keArr.length==2){
			var chuChuanArr=[];
			for(i=0;i<2;i++){
				var chuChuanIndex=keArr[i]+keArr[i]*2+1;
				if(_skyYinYang[siKe[0]]==_earthYinYang[siKe[chuChuanIndex]]){
					chuChuanArr.push(siKe[chuChuanIndex]);
				}
			}
			if(chuChuanArr.length==1){
				chuChuan=chuChuanArr[0];
				zhongChuan=tianPan[chuChuan];
				moChuan=tianPan[zhongChuan];
				return [chuChuan,zhongChuan,moChuan];
			}
		}
	}
	
	console.log(chuChuan,zhongChuan,moChuan);
}

function test(){
	var t=qiShi("2014","01","12","11","16","00"); 
	var pan=tianDiPan(t[7],t[8]);
	var sk=siKe(t[4],t[5],pan);
	var sc=sanChuan(sk,pan);
	console.log(t,pan,sk,sc);
}