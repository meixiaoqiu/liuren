//生克关系 com2.js用到
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

//根据年份参数返回60花甲年 首页计算年命用到
function huaJia(year){
	var sixty=(year-_firstYear)%60; //当前六十花甲序数
	var sky=sixty%10; //当前天干
	var earth=sixty%12; //当前地支
	//console.log(_skyStr[sky],_earthStr[earth]);
	return [sky,earth];
}

function xingNian(year) {
	
}

function benMing(){
}