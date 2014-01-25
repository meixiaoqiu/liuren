var _jiaZi=4; //第一个甲子年为公元4年
var _diZhi=[0,1,2,3,4,5,6,7,8,9,10,11]; //十二地支
var _diZhiStr=["子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥"];
var _tianGan=[0,1,2,3,4,5,6,7,8,9]; //十天干
var _tianGanStr=["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];
var _jieQi=["春分","清明","谷雨","立夏","小满","芒种","夏至","小暑","大暑","立秋","处暑","白露","秋分","寒露","霜降","立冬","小雪","大雪","冬至","小寒","大寒","立春","雨水","惊蛰"];
var _yueJiang=[1,0,11,10,9,8,7,6,5,4,3,2,1,0];//月将，从冬至之后第一个月将开始。因为算出的中
var _yueJiangStr=["丑","子","亥","戌","酉","申","未","午","巳","辰","卯","寅","丑","子"];//月将，从冬至之后第一个月将开始。因为算出的中气数组可能有14个，所以月将也为14个
var _jiGong=[2,4,5,7,5,7,8,10,11,1]; //十干寄宫
var _jiGongZhi=[-1];
var _wuXing=[0,1,2,3,4];
var _wuXingStr=["金","木","土","水","火"];
var _diZhiWuXing=[3,2,1,1,2,4,4,2,0,0,2,3]; //地支五行
var _tianGanWuXing=[1,1,4,4,2,2,0,0,3,3]; //天干五行
var _diZhiYinYang=[1,0,1,0,1,0,1,0,1,0,1,0]; //地支阴阳 0阴 1阳
var _tianGanYinYang=[1,0,1,0,1,0,1,0,1,0]; //天干阴阳
var _siMeng=[2,5,8,11]; //四孟 寅申巳亥
var _siZhong=[0,3,6,9]; //四仲 子午卯酉
var _siJi=[1,4,7,10]; //四季 辰戌丑未
var _diZhiXing=[3,10,5,0,4,8,6,1,2,9,7,11]; //地支相刑
var _diZhiChong=[6,7,8,9,10,11,0,1,2,3,4,5]; //地支相冲
var _diZhiYiMa=[2,11,8,5,2,11,8,5,2,11,8,5]; //地支驿马
var _tianGanHe=[5,6,7,8,9,0,1,2,3,4]; //天干相合


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

//卦
var g={
	siZhu:[],
	yueJiang:0,
	tianpan:[],
	siKe:[],
	
	siKeWuXing:[],
	siKeShengKe:[],
	siKeYinYang:[],
	zei:[],
	ke:[],
	siKeUnique:[], //四课中有效组合 从昴星课开始用到。
	
	biYong:[], //用于判断完比用课之后留给涉害课使用
	
	chuChuan:0,
	zhongChuan:0,
	moChuan:0,
	
	xunDun:[],
	
	qiKe:function(siZhu,yueJiang){
		this.siZhu=siZhu;
		this.yueJiang=yueJiang;
		this.tianDiPan();
		this.siKe();
		this.sanChuan();
	},
	
	//月将加时排天地盘
	tianDiPan:function(){
		var shiZhi=this.siZhu[7];
		var yueJiang=this.yueJiang;
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
		this.tianPan=tianPan;
	},
	
	//排四课
	siKe:function(){
		var riGan=this.siZhu[4];
		var riZhi=this.siZhu[5];
		var tianPan=this.tianPan;
		//第一课
		var ke1=[];
		ke1[0]=riGan;
		riGanJiGong=_jiGong[riGan];
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
		this.siKe= [ke1[0],ke1[1],ke2[0],ke2[1],ke3[0],ke3[1],ke4[0],ke4[1]];
	},
	
	//排三传
	sanChuan:function(){
		//循环四课的八个值，得出五行值
		for(i=0;i<this.siKe.length;i++){
			if(i==0){
				this.siKeWuXing[i]=_tianGanWuXing[this.siKe[i]];
			}else{
				this.siKeWuXing[i]=_diZhiWuXing[this.siKe[i]];
			}
		}
		
		//四课的五行生克关系
		var j=0;
		for(i=0;i<this.siKeWuXing.length;i++){
			if((i+2)%2==0){
				this.siKeShengKe[j]=shengKe(this.siKeWuXing[i],this.siKeWuXing[i+1]);
				j++;
			}
		}
		
		for(i=0;i<this.siKeShengKe.length;i++) {
			if(this.siKeShengKe[i]==-1){
				this.ke.push(i);
			}
			if(this.siKeShengKe[i]==1){
				this.zei.push(i);
			}
		}
		
		//依次判断九宗门
		var r=this.zeiKe();
		console.log(r);
		if(!r){
			r=this.biYong();
			console.log(r);
		}
		if(!r){
			r=this.sheHai();
			console.log(r);
		}
		if(!r){
			r=this.yaoKe();
			console.log(r);
		}
		if(!r){
			r=this.maoXing();
			console.log(r);
		}
		if(!r){
			r=this.bieZe();
			console.log(r);
		}
		if(!r){
			r=this.fuYin();
			console.log(r);
		}
		if(!r){
			r=this.fanYin();
			console.log(r);
		}
		if(!r){
			r=this.baZhuan();
			console.log(r);
		}
	},
	
	//旬遁
	xunDun:function(){
		//算出是什么旬，找出本旬第一天的支
		var firstZhi=this.siZhu[5]-this.siZhu[4];
		if(firstZhi<0){
			firstZhi=12+firstZhi;
			var xunDunFirst=this.tianPan[0]+firstZhi;
			for(i=0;i<_diZhi.length;i++){
				var xunDun=xunDunFirst+i;
				if(xunDun<_diZhi.length){
					this.xunDun.push(xunDun);
				}else{
					this.xunDun.push(xunDun-_diZhi.length);
				}
			}
		}
	},
	
	//是否为贼克
	zeiKe:function(){
		var re=false;
		var chuChuanIndex=-1;
		//始入 or 重审
		if(this.zei.length==1){
			var chuChuanIndex=this.zei[0]*2;
			re=true;
		}
		
		//元首
		if(this.ke.length==1 && this.zei.length==0){
			var chuChuanIndex=this.ke[0]*2;
			re=true;
		}
		
		if(chuChuanIndex>=0){
			this.chuChuan=this.siKe[chuChuanIndex];
			this.zhongChuan=this.tianPan[this.chuChuan];
			this.moChuan=this.tianPan[this.zhongChuan];
		}
		return re;
	},
	
	//是否为比用
	biYong:function(){
		var re=false;
		//比用:四课中有2或3课下贼上或2到3课上克下，则取与日干相比者为初传，两下贼上为比用，两上克下为知一
		var biYong=[]; //比用备选数组
		//判断是2下贼上还是2上克下，优先下贼上
		var zeiOrKe=[];
		var zeiOrKeType; //-1为上克下 1为下贼上
		if(this.ke.length>1){
			zeiOrKe=keArr;
			zeiOrKeType=-1;
		}
		if(this.zei.length>1){
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
				this.chuChuan=biYong[0];
				this.zhongChuan=this.tianPan[this.chuChuan];
				this.moChuan=this.tianPan[this.zhongChuan];
				re=true;
			}
		}
		this.biYong=biYong;
		return re;
	},
	
	//是否为涉害
	sheHai:function(){
		var re=false;
		//涉害：只取孟仲.
		//如果有贼或有克，且不是比用
		if( (this.biYong.length==0 || this.biYong.length>1) && (this.zei.length>0 || this.ke.length>0) ){
			//找四课里的孟仲
			var meng,zhong; //孟仲
			for(i=0;i<this.siKe.length/2;i++){
				var v;
				//如果是第一课 先转为地支
				if(i==0){
					v=_jiGong[this.siKe[i]];
				}else{
					v=this.siKe[i*2];
				}
				
				for(j=0;j<_siMeng.length;j++){
					if(v==_siMeng[j]){
						meng.push(v);
					}
					if(v==_siZhong[j]){
						zhong.push(v);
					}
				}
			}
			
			if(zhong.length>0){
				this.chuChuan=zhong[0];
				this.zhongChuan=this.tianPan[this.chuChuan];
				this.moChuan=this.tianPan[this.zhongChuan];
				re=true;
			}
			if(meng.length>0){
				this.chuChuan=meng[0];
				this.zhongChuan=this.tianPan[this.chuChuan];
				this.moChuan=this.tianPan[this.zhongChuan];
				re=true;
			}
		}
		return re;
	},
	
	//todo 涉害深浅法
	
	//是否为遥克
	yaoKe:function(){
		var re=false;
		
		//如果干支同位，不能取遥克，按八专论
		if(_jiGong[this.siKe[0]]!=this.siKe[4]){
			//找出所有遥克组合
			var yaoZei=[];
			var yaoKe=[];
			for(i=0;i<this.siKe.length/2;i++){
				var shangShenIndex=i+i*2+1;
				if(shengKe(this.siKeWuXing[0],this.siKeWuXing[shangShenIndex])==1){
					yaoZei.push(i);
				}
				if(shengKe(this.siKeWuXing[0],this.siKeWuXing[shangShenIndex])==-1){
					yaoKe.push(i);
				}
			}
			
			var chuChuanIndex;
			var yaoKeOrYaoZei=[];
			//先看是否有上神遥克日干
			if(yaoKe.length>0){
				yaoKeOrYaoZei=yaoKe;
			}else if(yaoZei.length>0){
				yaoKeOrYaoZei=yaoZei;
			}
			
			if(yaoKeOrYaoZei.length>0){
				if(yaoKeOrYaoZei.length==1){
					chuChuanIndex=yaoKeOrYaoZei[0]*2+1;
				}else if (yaoKeOrYaoZei.length>1){
					for(i=0;i<yaoKeOrYaoZei.length;i++){
						var shangShenIndex=yaoKeOrYaoZei[i]*2+1;
						if(_diZhiYinYang[this.siKe[shangShenIndex]]==_tianGanYinYang[this.siKe[0]]){
							chuChuanIndex=shangShenIndex;
						}
					}
				}
				this.chuChuan=this.siKe[chuChuanIndex];
				this.zhongChuan=this.tianPan[this.chuChuan];
				this.moChuan=this.tianPan[this.zhongChuan];
				re=true;
			}
		}

		return re;
	},
	//是否为昴星
	maoXing:function(){
		var re=false;
		
		//先看四课有几课
		var shangShen=[]; //四课下神数组
		for(i=0;i<this.siKe.length/2;i++){
			var shangShenIndex=i+i*2+1;
			shangShen.push(this.siKe[shangShenIndex]);
		}
		
		var str=shangShen.join("");
		for(i=0;i<shangShen.length;i++){
			var reg=new RegExp(shangShen[i],"g");
			var c=str.match(reg);
			if(c<2){
				this.siKeUnique.push(i);
			}
		}
		
		//当四课齐全时起昴星
		if(this.siKeUnique.length>=4){
			switch (this.siKeYinYang[0]) {
				case 0: //阴日取酉下神
					var chuChuanIndex;
					if(this.tianPan[0]>=9){
						chuChuanIndex=9+this.tianPan[0]-9;
					}else {
						chuChuanIndex=9-this.tianPan[0];
					}
					this.chuChuan=this.tianPan[chuChuanIndex];
					this.zhongChuan=this.siKe[1];
					this.moChuan=this.siKe[3];
					break;
				
				case 1: //阳日取酉上神
					this.chuChuan=this.tianPan[9];
					this.zhongChuan=this.siKe[3];
					this.moChuan=this.siKe[1];
					break;
			}
			re=true;
		}
		return re;
	},
	
	//是否为别责
	bieZe:function(){
		var re=false;
		if(this.siKeUnique.length==3){
			//阳日取干合之上神为初传，阴日取支前三合为初传
			switch(_tianGanYinYang[this.siKe[0]]){
				case 1:
					this.chuChuan=_jiGong[_tianGanHe[this.siKe[0]]];
					break;
				
				case 0:
					this.chuChuan=this.siKe[4]+4;
					if(this.chuChuan>11){
						this.chuChuan=this.siKe[4]-8;
					}
					break;
			}
			this.zhongChuan=this.moChuan=this.siKe[1];
			re=true;
		}
		return re;
	},
	
	//是否为伏吟
	fuYin:function(){
		var re=false;
		if(this.tianPan[0]==_diZhi[0]){
			var another=this.siKe[3];
			if( shengKe(this.siKe[0],this.siKe[1])==-1 || shengKe(this.siKe[0],this.siKe[1])==1 ){
				this.chuChuan=this.siKe[1];
			}else{
				switch (_tianGanYinYang[this.siKe[0]]) {
					case 1:
						this.chuChuan=this.siKe[1];
						another=this.siKe[3];
						break;
						
					case 0:
						this.chuChuan=this.siKe[3];
						another=this.siKe[1];
						break;
				}
			}
			this.zhongChuan=_diZhiXing[this.chuChuan];
			if(this.zhongChuan==this.chuChuan){
				this.zhongChuan=another;
			}
			this.moChuan=_diZhiXing[this.zhongChuan];
			if(this.moChuan==this.zhongChuan){
				this.moChuan=_diZhiChong[this.moChuan];
			}
			//丁己辛三日，末传为午
			if(this.siKe[0]==3 || this.siKe[0]==5 || this.siKe[0]==7){
				this.moChuan=6;
			}
			re=true;
		}
		return re;
	},
	
	//是否为反吟
	fanYin:function(){
		var re=false;
		if(_diZhiChong[this.tianPan[0]]==this.tianPan[6]){
			//如有贼克，之前就已经走贼克方法排出了
			//如无贼克，驿马为初传,辰中日末
			this.chuChuan=_diZhiYiMa[this.siKe[4]];
			this.zhongChuan=this.siKe[5];
			this.moChuan=this.siKe[1];
			re=true;
		}
		return re;
	},
	
	//是否为八专
	baZhuan:function(){
		var re=false;
		//干支同位
		if(_jiGong[this.siKe[0]]==this.siKe[4]){
			//如有贼克，之前就已经走贼克方法排出了
			switch(_tianGanYinYang[this.siKe[0]]){
				case 1:
					this.chuChuan=this.siKe[1]+2;
					if(this.chuChuan>11){
						this.chuChuan=this.chuChuan-11;
					}
					break;
				
				case 0:
					this.chuChuan=this.siKe[7]-2;
					if(this.chuChuan<0){
						this.chuChuan=this.chuChuan+12;
					}
					break;
			}
			this.zhongChuan=this.moChuan=this.siKe[1];
		}
		return re;
	}
	
	
	
	
	
}