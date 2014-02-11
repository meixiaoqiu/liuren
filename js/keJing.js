//64课经
var K={
	fData:[], //father data 父级数据，计算本课属于64课中的哪课
	cData:[], //child data 子级数据，有些课式有多种子格局
	
	init:function (){
		this.fData=[];
		this.cData=[];
	},
	
	action:function(){
		this.init();
		this.filter();
	},
	
	filter:function(){
		for(i=0;i<9;i++){
			eval("this.k"+i+"();");
		}
	},
	
	//元首
	k0:function(){
		if(G.ke.length==1 && G.zei.length==0 && G.tianPan[0]!=_diZhi[0]){
			this.fData.push(0);
		}
	},
	
	//重审
	k1:function(){
		if(G.zei.length==1 && G.ke.length==0){
			this.fData.push(1);
		}
	},
	
	//知一
	k2:function(){
		if(G.biYong.length==1){
			this.fData.push(2);
		}
	},
	
	//涉害
	k3:function(){
		if( (G.biYong.length==0||G.biYong.length>1) && (G.zei.length>1 || G.ke.length>1)  ){
			this.fData.push(3);
			
			var chuChuanTianPanIndex=-1;
			for(i=0;i<G.tianPan.length;i++){
				if(G.sanChuan[0]==G.tianPan[i]){
					chuChuanTianPanIndex=i;
				}
			}
			
			var jianJi=false;
			var chaWei=false;
			var zhuiXia=false;
			for(i=0;i<_siMeng.length;i++){
				if(chuChuanTianPanIndex==_siMeng[i]){
					jianJi=true;
				}
				if(chuChuanTianPanIndex==_siZhong[i]){
					chaWei=true;
				}
				if(chuChuanTianPanIndex==_siJi[i]){
					zhuiXia=true;
				}
			}
			
			if(jianJi==true){
				this.cData.push(0);//见机格
			}
			if(chaWei==true){
				this.cData.push(1);//察微格
			}
			if(zhuiXia==true){
				this.cData.push(2);//缀霞格
			}
			
			//todo 处理比用格
		}
	},
	
	//遥克
	k4:function(){
		if(G.yaoZei.length==1 || G.yaoKe.length==1){
			this.fData.push(4);
			if(G.yaoKe.length>0){
				this.cData.push(3); //蒿矢
			}else{
				if(G.yaoZei.length>0){
					this.cData.push(4); //弹射
				}
			}
		}
	},
	
	//昴星
	k5:function(){
		if(G.zei.length<=0 && G.ke.length<=0 && G.yaoZei.length<=0 && G.yaoKe.length<=0 && G.siKeRepeat==0){
			this.fData.push(5);
			if(_tianGanYinYang[G.siKe[0]]==1){
				this.cData.push(5); //虎视转蓬
			}else{
				this.cData.push(6); //冬蛇掩目
			}
		}
	},
	
	//别责
	k6:function(){
		if(G.zei.length<=0 && G.ke.length<=0 && G.yaoZei.length<=0 && G.yaoKe.length<=0 && G.siKeRepeat==2){
			this.fData.push(6);
		}
	},
	
	//八专
	k7:function(){
		if(G.zei.length<=0 && G.ke.length<=0 && G.siKe[4]==_jiGong[G.siKe[0]]){
			this.fData.push(7);
		}
		//todo 帷簿不修 独足
	},
	
	//伏吟
	k8:function(){
		if(G.tianPan[0]==_diZhi[0]){
			this.fData.push(8);
		}
		//todo 自任 杜传
	}
};

var Kstr=[
	//0:元首
	{
		bagua:0,
		name:"元首课",
		description:"凡一上克下，余课无克，为元首课，象天。如君克臣，必顺其正，无乱动反常之理。为九宗之元，六十四课之首，故名元首。君占则有伊尹之臣，臣占必遇唐虞之君；常人占之，万事顺利。大哉元首，元亨利贞，首出庶物，万国咸宁，统干之本，乃元吉第一课也。",
		xiangYue:"天地得位，品物咸新。事用君子，忧喜俱真。君臣和合，父子慈亲。婚谐鸾凤，孕育麒麟。用兵客胜，论讼先陈。市贾出色，各利超群。官职首擢，柱石元勋。门庭喜溢，利见大人。", //象曰
		description2:"如日用神年命值旺相气，乘吉将，更逢富贵、龙德、时泰、三光、三阳、官爵、高盖吉课，有一助之，则有干之九五“飞龙在天。”云龙风虎相从，大人之象也。"
	},
	//1:重审
	{
		bagua:1,
		name:"重审课",
		description:"凡一下贼上，余课无克，为重审课。象地，事逆，以下犯上，如臣诤君，不敢擅为，必再三详审定计而后入，故名重审。积善者庆，积不善者殃。君子占之，利有攸往。至哉重审，含章可贞，或从王事，无成有终，统坤之体也。",
		xiangYue:"顺天厚载，柔顺利贞。一下逆上，岂无忧惊？贵顺福至，贵逆乱兴。事宜后起，祸从内生。用兵主胜，受孕女形。诸般谋望，先难后成。",
		description2:"如初传墓绝，末传生旺，灾祸自消。生旺传墓绝，不吉；墓绝传生旺，吉。初传克末凶，末传克初吉。或逢龙、常、阴、后、六合吉将，生气、解神、天德、月德、天喜、德神、合神吉神得一在末传，可化凶为吉。君子厚德，中道而行，则有坤六五“黄裳，元吉。”之象也。"
	},
	//2:知一
	{
		bagua:7,
		name:"知一课",
		description:"凡课有二上克下，或二下克上，择课之阴阳与今日比者而为用神，曰知一课。比者各也，阳日阳比，阴日阴比。二爻皆动，事有两岐，善恶混处，必知择其比和一善者而用之，故名知一。事宜惟一，允执厥中。占物占人，皆在近也。统“比”之体，乃去谗任贤之课也。比者，亲辅也，有不宁方来意。",
		xiangYue:"比者为喜，不比为忧。词宜和允，兵利主谋。祸从外起，事向朋谋。寻人失物，近处堪求。",
		description2:"如课下克上有嫉妒，日辰贵后主迟疑，或至三克度厄，四克无禄，乱动无狐疑，则有上六“比之无首”凶象。或上克下有嫌疑，日辰贵前主事顺，及止二克，贞固择一，则有比六二“自内，贞吉”之象也。"
	},
	//3:涉害
	{
		bagua:28,
		name:"涉害课",
		description:"",
		xiangYue:"",
		description2:""
	},
	//4:遥克
	{
		bagua:37,
		name:"遥克课",
		description:"",
		xiangYue:"",
		description2:""
	},
	//5
	{
		bagua:9,
		name:"昴星课",
		description:"",
		xiangYue:"",
		description2:""
	},
	//6
	{
		bagua:-1, //无对应的六十四卦
		name:"别责课",
		description:"",
		xiangYue:"",
		description2:""
	},
	//7
	{
		bagua:12,
		name:"八专课",
		description:"",
		xiangYue:"",
		description2:""
	},
	//8
	{
		bagua:51,
		name:"伏吟课",
		description:"",
		xiangYue:"",
		description2:""
	}
]

//六十四卦
var _baGua=[
	["乾","䷀"], //0
	["坤","䷁"], //1
	["屯","䷂"], //2
	["蒙","䷃"], //3
	["需","䷄"], //4
	["讼","䷅"], //5
	["师","䷆"], //6
	["比","䷇"], //7
	["小畜","䷈"], //8
	["履","䷉"], //9
	["泰","䷊"], //10
	["否","䷋"], //11
	["同人","䷌"], //12
	["大有","䷍"], //13
	["谦","䷎"], //14
	["豫","䷏"], //15
	
	["随","䷐"], //16
	["蛊","䷑"], //17
	["临","䷒"], //18
	["观","䷓"], //19
	["噬嗑","䷔"], //20
	["贲","䷕"], //21
	["剥","䷖"], //22
	["复","䷗"], //23
	["无妄","䷘"], //24
	["大畜","䷙"], //25
	["颐","䷚"], //26
	["大过","䷛"], //27
	["坎","䷜"], //28
	["离","䷝"], //29
	["咸","䷞"], //30
	["恒","䷟"], //31
	
	["遁","䷠"], //32
	["大壮","䷡"], //33
	["晋","䷢"], //34
	["明夷","䷣"], //35
	["家人","䷤"], //36
	["睽","䷥"], //37
	["蹇","䷦"], //38
	["解","䷧"], //39
	["损","䷨"], //40
	["益","䷩"], //41
	["夬","䷪"], //42
	["姤","䷫"], //43
	["萃","䷬"], //44
	["升","䷭"], //45
	["困","䷮"], //46
	["井","䷯"], //47
	//															
	["革","䷰"], //48
	["鼎","䷱"], //49
	["震","䷲"], //50
	["艮","䷳"], //51
	["渐","䷴"], //52
	["归妹","䷵"], //53
	["丰","䷶"], //54
	["旅","䷷"], //55
	["巽","䷸"], //56
	["兑","䷹"], //57
	["涣","䷺"], //58
	["节","䷻"], //59
	["中孚","䷼"], //60
	["小过","䷽"], //61
	["既济","䷾"], //62
	["未济","䷿"], //63
];