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


//卦
var g={
	siZhu:[],
	yueJiang,
	tianpan:[],
	siKe:[],
	
	siKeWuXing,
	siKeShengKe,
	siKeYinYang,
	zei:[],
	ke:[],
	siKeUnique:[]; //四课中有效组合 从昴星课开始用到。
	
	biYong:[], //用于判断完比用课之后留给涉害课使用
	
	chuChuan,
	zhongChuan,
	moChuan,
	
	init:function(siZhu,yueJiang){
		this.siZhu=siZhu;
		this.yueJiang=yueJiang;
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
	function siKe(){
		var riGan=this.siZhu[4];
		var riZhi=this.siZhu[5];
		var tianPan=this.tianPan;
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
		this.siKe= [ke1[0],ke1[1],ke2[0],ke2[1],ke3[0],ke3[1],ke4[0],ke4[1]];
	},
	
	//排三传
	function sanChuan(){
		//循环四课的八个值，得出五行值
		var this.siKeWuXing=[];
		for(i=0;i<this.siKe.length;i++){
			if(i==0){
				this.siKeWuXing[i]=_skyFive[this.siKe[i]];
			}else{
				this.siKeWuXing[i]=_earthFive[this.siKe[i]];
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
		if(!r){
			r=this.biYong();
		}
		if(!r){
			r=this.sheHai();
		}
		if(!r){
			r=this.yaoKe();
		}
		if(!r){
			r=this.maoXing();
		}
		if(!r){
			r=this.fuYin();
		}
		if(!r){
			r=this.fanYin();
		}
		if(!r){
			r=this.bieZe();
		}
		if(!r){
			r=this.baZhuan();
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
		var zeiOrKe;
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
	//是否为遥克
	yaoKe:function(){
		var re=false;
		
		//找出所有遥克组合
		for(i=0;i<this.siKe.length/2;i++){
			var shangShenIndex=i+i*2+1;
			if(shengKe(this.siKeWuXing[0],this.siKeWuXing[shangShenIndex])==1){
				this.zei.push(i);
			}
			if(shengKe(this.siKeWuXing[0],this.siKeWuXing[shangShenIndex])==-1){
				this.ke.push(i);
			}
		}
		
		//用遥贼克数组重新判断依次贼克课和比用课
		re=this.zeiKe();
		if(!re){
			re=this.biYong();
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
			shangShen.push(siKe[shangShenIndex]);
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
				case 0:
			}
		}
		return re;
	},
	//是否为伏吟
	fuYin:function(){
		return false;
	},
	//是否为反吟
	fanYin:function(){
		return false;
	},
	//是否为别责
	bieZe:function(){
		return false;
	},
	//是否为八专
	baZhuan:function(){
		return false;
	}
	
	
	
	
	
}