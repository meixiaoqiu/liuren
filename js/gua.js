//卦
var gStr={
	siZhu:[],
	yueJiang:"",
	tianPan:[],
	siKe:[],
	
	siKeWuXing:[],
	siKeShengKe:[],
	siKeYinYang:[],
	siKeTianJiang:[],
	
	sanChuan:[],
	sanChuanDunGan:[],
	sanChuanTianJiang:[],
	
	dunGan:[],
	tianJiang:[],
	liuQin:[]
};

var g={
	siZhu:[],
	yueJiang:0,
	tianPan:[],
	siKe:[],
	
	siKeWuXing:[],
	siKeShengKe:[],
	siKeYinYang:[],
	siKeTianJiang:[],
	
	zei:[],
	ke:[],
	siKeUnique:[], //四课中有效组合 从昴星课开始用到。
	
	biYong:[], //用于判断完比用课之后留给涉害课使用
	zeiOrKe:[], //用于涉害法
	zeiOrKeType:[], //用于涉害法
	yaoKe:[],//用于遥克法
	yaoZei:[],//用于遥克法
	
	sanChuan:[],
	sanChuanDunGan:[],
	sanChuanTianJiang:[],
	keName:'',
	
	dunGan:[],
	tianJiang:[],
	liuQin:[], //三传六亲
	
	init:function(){
		this.siZhu=[];
		this.yueJiang=0;
		this.tianPan=[];
		this.siKe=[];
		
		this.siKeWuXing=[];
		this.siKeShengKe=[];
		this.siKeYinYang=[];
		this.zei=[];
		this.ke=[];
		this.siKeUnique=[];
		
		this.biYong=[];
		this.zeiOrKe=[];
		this.zeiOrKeType=[];
		this.yaoKe=[];
		this.yaoZei=[];
		
		this.sanChuan=[];
		this.sanChuanDunGan=[];
		this.sanChuanTianJiang=[];
		
		this.dunGan=[];
		this.tianJiang=[];
		this.liuQin=[];
	},
	
	toStr:function(){
		for(i=0;i<this.siZhu.length;i++){
			gStr.siZhu[i]=_diZhiStr[this.siZhu[i]];
		}
		
		gStr.yueJiang=_diZhiStr[this.yueJiang];
		
		for(i=0;i<this.tianPan.length;i++){
			gStr.tianPan[i]=_diZhiStr[this.tianPan[i]];
		}
		
		var k=0;
		for(i=0;i<this.siKe.length;i++){
			if(i==0){
				gStr.siKe[i]=_tianGanStr[this.siKe[i]];
			}else{
				gStr.siKe[i]=_diZhiStr[this.siKe[i]];
			}
			//算出四课天将
			if((i+2)%2==1){
				for(j=0;j<this.tianPan.length;j++){
					if(this.tianPan[j]==this.siKe[i]){
						this.siKeTianJiang[k]=this.tianJiang[j];
						gStr.siKeTianJiang[k]=_tianJiang[this.tianJiang[j]];
					}
				}
				k++;
			}
		}
		
		for(i=0;i<this.siKeWuXing.length;i++){
			gStr.siKeWuXing[i]=_wuXingStr[this.siKeWuXing[i]];
		}
		
		for(i=0;i<this.siKeYinYang.length;i++){
			gStr.siKeYinYang[i]=_yinYang[this.siKeYinYang[i]];
		}
		
		for(i=0;i<this.sanChuan.length;i++){
			gStr.sanChuan[i]=_diZhiStr[this.sanChuan[i]];
			gStr.sanChuanDunGan[i]=_tianGanStr[this.sanChuanDunGan[i]];
			//算出三传天将
			for(j=0;j<this.tianPan.length;j++){
				if(this.tianPan[j]==this.sanChuan[i]){
					gStr.sanChuanTianJiang[i]=_tianJiang[this.tianJiang[j]];
				}
			}
		}
		
		for(i=0;i<this.dunGan.length;i++){
			if(this.dunGan[i]<=9){
				gStr.dunGan[i]=_tianGanStr[this.dunGan[i]];
			}else{
				gStr.dunGan[i]="旬空";
			}
			
		}
		
		for(i=0;i<this.tianJiang.length;i++){
			gStr.tianJiang[i]=_tianJiang[this.tianJiang[i]];
		}
		
		for(i=0;i<this.liuQin.length;i++){
			switch(this.liuQin[i]){
				case 1:
					gStr.liuQin[i]="妻财";
					break;
				
				case -1:
					gStr.liuQin[i]="官鬼";
					break;
					
				case 2:
					gStr.liuQin[i]="子孙";
					break;
					
				case -2:
					gStr.liuQin[i]="父母";
					break;
					
				case 0:
					gStr.liuQin[i]="兄弟";
					break;
			}
		}
	},
	
	qiKe:function(siZhu,yueJiang){
		this.init();
		
		this.siZhu=siZhu;
		this.yueJiang=yueJiang;
		this.tianDiPan();
		this.getSiKe();
		this.getSanChuan();
		this.xunDun();
		this.qiGuiRen();
		this.getLiuQin();
		
		this.toStr();
	},
	
	//月将加时排天地盘
	tianDiPan:function(){
		var shiZhi=this.siZhu[7];
		var yueJiang=this.yueJiang;
		var tianPan=[];
		var tianPanFirst=0;
		if(shiZhi<=yueJiang){
			tianPanFirst=yueJiang-shiZhi;
		}else{
			tianPanFirst=yueJiang*1+12-shiZhi;
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
	getSiKe:function(){
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
	getSanChuan:function(){
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
		if(!r){
			r=this.biYongKe();
		}
		if(!r){
			r=this.sheHai2();
		}
		if(!r){
			r=this.yaoKeKe ();
		}
		if(!r){
			r=this.maoXing();
		}
		if(!r){
			r=this.bieZe();
		}
		if(!r){
			r=this.fuYin();
		}
		if(!r){
			r=this.fanYin();
		}
		if(!r){
			r=this.baZhuan();
		}
		//console.log(this.keName);
	},
	
	//旬遁
	xunDun:function(){
		//算出是什么旬，找出本旬第一天的支
		var firstZhi=this.siZhu[5]-this.siZhu[4];
		if(firstZhi<0){
			firstZhi=12+firstZhi;
		}
		var dunGanFirst=this.tianPan[0]+firstZhi;
		for(i=0;i<_diZhi.length;i++){
			var dunGan=dunGanFirst+i;
			if(dunGan<_diZhi.length){
				this.dunGan[i]=dunGan;
			}else{
				this.dunGan[i]=dunGan-_diZhi.length;
			}
		}
		
		//算出三传遁干
		for(i=0;i<this.tianPan.length;i++){
			switch (this.tianPan[i]) {
				case this.sanChuan[0]:
					this.sanChuanDunGan[0]=this.dunGan[i];
					break;
						
				case this.sanChuan[1]:
					this.sanChuanDunGan[1]=this.dunGan[i];
					break;
						
				case this.sanChuan[2]:
					this.sanChuanDunGan[2]=this.dunGan[i];
					break;
			}
		}
	},
	
	//天将 起贵人
	qiGuiRen:function(){
		var guiRen;
		if(this.siZhu[7]>=3 && this.siZhu[7]<=8){
			//日贵
			if(this.siZhu[4]==0 || this.siZhu[4]==4 || this.siZhu[4]==6){
				//甲戊庚牛羊
				guiRen=1;
			}
			if(this.siZhu[4]==1 || this.siZhu[4]==5){
			//乙己鼠猴乡
				guiRen=0;
			}
			if(this.siZhu[4]==2 || this.siZhu[4]==3){
			//丙丁猪鸡位
				guiRen=11;
			}
			if(this.siZhu[4]==8 || this.siZhu[4]==9){
			//壬癸蛇兔藏
				guiRen=5;
			}
			if(this.siZhu[4]==7){
			//六辛逢马虎
				guiRen=6;
			}
		}else{
			//夜贵
			if(this.siZhu[4]==0 || this.siZhu[4]==4 || this.siZhu[4]==6){
			//甲戊庚牛羊
				guiRen=7;
			}
			if(this.siZhu[4]==1 || this.siZhu[4]==5){
			//乙己属猴乡
				guiRen=8;
			}
			if(this.siZhu[4]==2 || this.siZhu[4]==3){
			//丙丁猪鸡位
				guiRen=9;
			}
			if(this.siZhu[4]==8 || this.siZhu[4]==9){
			//壬癸蛇兔藏
				guiRen=3;
			}
			if(this.siZhu[4]==7){
			//六辛逢马虎
				guiRen=2;
			}
		}
		
		//判断贵人顺逆
		var guiRenIndex; //贵人在天盘的序号
		for(i=0;i<this.tianPan.length;i++){
			if(this.tianPan[i]==guiRen){
				guiRenIndex=i;
			}
		}
		
		//通过贵人索引推算出天盘第一个位置的天将
		var tianJiangFirst;
		var shunXing=true; //贵人是否顺行
		if(guiRenIndex>=5 && guiRenIndex<=10){ //顺布则背天门，逆布则向地户
			tianJiangFirst=guiRenIndex;
			shunXing=false;
		}else{
			tianJiangFirst=12-guiRenIndex;
		}
		
		for(i=0;i<_diZhi.length;i++){
			if(shunXing){
				var jiang=tianJiangFirst+i;
			}else{
				var jiang=tianJiangFirst-i;
			}
			
			this.tianJiang[i]=jiang;
			if(jiang>=_diZhi.length){
				this.tianJiang[i]=jiang-_diZhi.length;
			}
			if(jiang<0){
				this.tianJiang[i]=_diZhi.length+jiang;
			}
		}
		//console.log(guiRen,guiRenIndex,tianJiangFirst,shunXing,this.siZhu[4],this.tianJiang);
	},
	
	//六亲
	getLiuQin:function(){
		//console.log(this.siZhu[0],this.sanChuan[0]);
		this.liuQin[0]=shengKe(this.siKeWuXing[0],_diZhiWuXing[this.sanChuan[0]]);
		this.liuQin[1]=shengKe(this.siKeWuXing[0],_diZhiWuXing[this.sanChuan[1]]);
		this.liuQin[2]=shengKe(this.siKeWuXing[0],_diZhiWuXing[this.sanChuan[2]]);
	},
	
	
	
	//是否为贼克
	zeiKe:function(){
		var re=false;
		var chuChuanIndex=-1;
		//始入 or 重审
		if(this.zei.length==1){
			var chuChuanIndex=this.zei[0]*2+1;
			re=true;
		}
		
		//元首
		if(this.ke.length==1 && this.zei.length==0){
			var chuChuanIndex=this.ke[0]*2+1;
			re=true;
		}
		
		if(chuChuanIndex>=0){
			this.sanChuan[0]=this.siKe[chuChuanIndex];
			this.sanChuan[1]=this.tianPan[this.sanChuan[0]];
			this.sanChuan[2]=this.tianPan[this.sanChuan[1]];
		}
		
		if(re){
			this.keName='zeiKe';
		}
		
		return re;
	},
	
	//是否为比用
	biYongKe:function(){
		var re=false;
		//比用:四课中有2或3课下贼上或2到3课上克下，则取与日干相比者为初传，两下贼上为比用，两上克下为知一
		var biYong=[]; //比用备选数组
		//判断是2下贼上还是2上克下，优先下贼上
		var zeiOrKe=[];
		var zeiOrKeType; //-1为上克下 1为下贼上
		if(this.ke.length>1){
			zeiOrKe=this.ke;
			zeiOrKeType=-1;
		}
		if(this.zei.length>1){
			zeiOrKe=this.zei;
			zeiOrKeType=1;
		}
		if(zeiOrKe.length>1){
			for(i=0;i<zeiOrKe.length;i++){
				var index=(zeiOrKe[i]+1)*2-1;
				if(_diZhiYinYang[this.siKe[index]]==_tianGanYinYang[this.siKe[0]]){
					biYong.push(this.siKe[index]);
				}
			}
			//如果biYong数组中有一个值则按比用法起课
			if(biYong.length==1){
				this.sanChuan[0]=biYong[0];
				this.sanChuan[1]=this.tianPan[this.sanChuan[0]];
				this.sanChuan[2]=this.tianPan[this.sanChuan[1]];
				re=true;
			}
		}
		this.biYong=biYong;
		this.zeiOrKe=zeiOrKe;
		this.zeiOrKeType=zeiOrKeType;
		
		if(re){
			this.keName='biYong';
		}
		return re;
	},
	
	//是否为涉害
	sheHai:function(){
		var re=false;
		//涉害：只取孟仲.
		//如果有贼或有克，且不是比用
		if( (this.biYong.length==0 || this.biYong.length>1) && (this.zei.length>0 || this.ke.length>0) ){
			//找四课里的孟仲
			var meng=[];
			var zhong=[]; //孟仲
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
				this.sanChuan[0]=zhong[0];
				this.sanChuan[1]=this.tianPan[this.sanChuan[0]];
				this.sanChuan[2]=this.tianPan[this.sanChuan[1]];
				re=true;
			}
			if(meng.length>0){
				this.sanChuan[0]=meng[0];
				this.sanChuan[1]=this.tianPan[this.sanChuan[0]];
				this.sanChuan[2]=this.tianPan[this.sanChuan[1]];
				re=true;
			}
		}
		
		if(re){
			this.keName='sheHai';
		}
		return re;
	},
	
	//涉害深浅法
	sheHai2:function(){
		var re=false;
		console.log(this.zeiOrKe);
		if(this.zeiOrKe.length>0){
			var sheHai=[];
			var nowBig=0; //目前最大涉害数
			var lastBig=-1; //最后一个最大涉害index
			
			//将贼组合或克组合的天盘涉归本家
			for(i=0;i<this.zeiOrKe.length;i++){
				var index=(this.zeiOrKe[i]+1)*2-1;
				var tianPanIndex=-1;
				for(k=0;k<_diZhi.length;k++){
					if(this.tianPan[i]==this.siKe[index]){
						tianPanIndex=i;
					}
				}
				console.log(tianPanIndex);
				//涉归本家
				sheHai[i]=0;
				for(j=0;j<_diZhi.length;j++){
					var index=tianPanIndex+i;
					if( shengKe(_diZhiWuXing[this.tianPan[index]],_diZhiWuXing[_diZhi[index]])==-this.zeiOrKeType ){
						sheHai[i]++;
					}
					//涉害天干
					if( shengKe(_diZhiWuXing[this.tianPan[index]],_tianGanWuXing[_jiGong[_diZhi[index]]])==-this.zeiOrKeType ){
						sheHai[i]++;
					}
				}
				
				if(sheHai[i]>nowBig){
					nowBig=sheHai[i];
				}
			}
			
			//轮询查找有几个最大涉害数
			var bigNum=0;
			for(i=0;i<sheHai.length;i++){
				if(nowBig==sheHai[i]){
					bigNum++;
					lastBig=i;
				}
			}
			//如果只有一个涉害最深者，发用
			var chuChuanIndex=-1;
			if(bigNum==1){
				chuChuanIndex=(this.zeiOrKe[lastBig]+1)*2-1;
			}
			
			//如果大于一个涉害最深者，依次取孟、仲上者
			var meng=[];
			var zhong=[];
			if(bigNum>1){
				for(j=0;j<sheHai.length;j++){
					var xiaShenIndex=(zeiOrKe[j]+1)*2-2;
					for(i=0;i<_siMeng.length;i++){
						if(this.siKe[xiaShenIndex]==_siMeng[j]){
							meng.push(this.siKe[xiaShenIndex+1]);
						}
						if(this.siKe[xiaShenIndex]==_siZhong[j]){
							zhong.push(this.siKe[xiaShenIndex+1]);
						}
					}
				}
			}
			if(zhong.length>0){
				this.sanChuan[0]=zhong[0];
				re=true;
			}
			if(meng.length>0){
				this.sanChuan[0]=meng[0];
				re=true;
			}
			if(chuChuanIndex>=0){
				this.sanChuan[0]=this.siKe[chuChuanIndex];
			}
			this.sanChuan[1]=this.tianPan[this.sanChuan[0]];
			this.sanChuan[2]=this.tianPan[this.sanChuan[1]];
			re=true;
		}
		return re;
		/*
		if( (this.biYong.length==0 || this.biYong.length>1) && (this.zei.length>0 || this.ke.length>0) ){
			//首先判断涉害类型是下贼上还是上克下
			var zeiOrKe=[];
			var zeiOrKeType; //-1为上克下 1为下贼上
			if(this.ke.length>1){
				zeiOrKe=this.ke;
				zeiOrKeType=-1;
			}
			if(this.zei.length>1){
				zeiOrKe=this.zei;
				zeiOrKeType=1;
			}
		}
		*/
	},
	
	//是否为遥克
	yaoKeKe:function(){
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
			
			this.yaoZei=yaoZei;
			this.yaoKe=yaoKe;
			
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
				this.sanChuan[0]=this.siKe[chuChuanIndex];
				this.sanChuan[1]=this.tianPan[this.sanChuan[0]];
				this.sanChuan[2]=this.tianPan[this.sanChuan[1]];
				re=true;
			}
		}

		if(re){
			this.keName='yaoKe';
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
					this.sanChuan[0]=this.tianPan[chuChuanIndex];
					this.sanChuan[1]=this.siKe[1];
					this.sanChuan[2]=this.siKe[3];
					break;
				
				case 1: //阳日取酉上神
					this.sanChuan[0]=this.tianPan[9];
					this.sanChuan[1]=this.siKe[3];
					this.sanChuan[2]=this.siKe[1];
					break;
			}
			re=true;
		}
		
		if(re){
			this.keName='maoXing';
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
					this.sanChuan[0]=_jiGong[_tianGanHe[this.siKe[0]]];
					break;
				
				case 0:
					this.sanChuan[0]=this.siKe[4]+4;
					if(this.sanChuan[0]>11){
						this.sanChuan[0]=this.siKe[4]-8;
					}
					break;
			}
			this.sanChuan[1]=this.sanChuan[2]=this.siKe[1];
			re=true;
		}
		
		if(re){
			this.keName='bieZe';
		}
		return re;
	},
	
	//是否为伏吟
	fuYin:function(){
		var re=false;
		if(this.tianPan[0]==_diZhi[0]){
			var another=this.siKe[3];
			if( shengKe(this.siKe[0],this.siKe[1])==-1 || shengKe(this.siKe[0],this.siKe[1])==1 ){
				this.sanChuan[0]=this.siKe[1];
			}else{
				switch (_tianGanYinYang[this.siKe[0]]) {
					case 1:
						this.sanChuan[0]=this.siKe[1];
						another=this.siKe[3];
						break;
						
					case 0:
						this.sanChuan[0]=this.siKe[3];
						another=this.siKe[1];
						break;
				}
			}
			this.sanChuan[1]=_diZhiXing[this.sanChuan[0]];
			if(this.sanChuan[1]==this.sanChuan[0]){
				this.sanChuan[1]=another;
			}
			this.sanChuan[2]=_diZhiXing[this.sanChuan[1]];
			if(this.sanChuan[2]==this.sanChuan[1]){
				this.sanChuan[2]=_diZhiChong[this.sanChuan[2]];
			}
			//丁己辛三日，末传为午
			if(this.siKe[0]==3 || this.siKe[0]==5 || this.siKe[0]==7){
				this.sanChuan[2]=6;
			}
			re=true;
		}
		
		if(re){
			this.keName='fuYin';
		}
		return re;
	},
	
	//是否为反吟
	fanYin:function(){
		var re=false;
		if(_diZhiChong[this.tianPan[0]]==this.tianPan[6]){
			//如有贼克，之前就已经走贼克方法排出了
			//如无贼克，驿马为初传,辰中日末
			this.sanChuan[0]=_diZhiYiMa[this.siKe[4]];
			this.sanChuan[1]=this.siKe[5];
			this.sanChuan[2]=this.siKe[1];
			re=true;
		}
		
		if(re){
			this.keName='fanYin';
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
					this.sanChuan[0]=this.siKe[1]+2;
					if(this.sanChuan[0]>11){
						this.sanChuan[1]=this.sanChuan[0]-11;
					}
					break;
				
				case 0:
					this.sanChuan[0]=this.siKe[7]-2;
					if(this.sanChuan[0]<0){
						this.sanChuan[0]=this.sanChuan[0]+12;
					}
					break;
			}
			this.sanChuan[1]=this.sanChuan[2]=this.siKe[1];
		}
		
		if(re){
			this.keName='baZhuan';
		}
		return re;
	}
	
	
	
	
	
}