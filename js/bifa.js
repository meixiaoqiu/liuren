//毕法
var B={
	data:[],
	
	init:function (){
		this.data=[];
	},
	
	action:function(){
		this.init();
		this.filter();
	},
	
	filter:function(){
		//前后引从升迁吉
		if(G.sanChuan[0]-2==G.sanChuan[2] || G.sanChuan[2]-G.sanChuan[0]==10){
			this.data.push(0);
			//引干
			if(G.sanChuan[0]-1==G.siKe[1] || G.sanChuan[2]+1==G.siKe[1]){
				this.data.push(1);
				//拱贵
				if(G.siKeTianJiang[0]==0){
					this.data.push(3);
				}
				//两贵引从天干格
				if( (G.sanChuan[0]==5 && G.sanChuan[2]==3) || (G.sanChuan[0]==11 && G.sanChuan[2]==9) ){
					//壬癸蛇兔藏 丙丁猪鸡位
					this.data.push(4);
				}
			}
			//引支
			if(G.sanChuan[0]-1==G.siKe[5] || G.sanChuan[2]+1==G.siKe[5]){
				this.data.push(2);
			}
		}
	}
};

var Bstr=[];
Bstr[0]="前后引从升迁吉";
Bstr[1]="引干宜进职";
Bstr[2]="引支宜迁宅";
Bstr[3]="拱贵格";
Bstr[4]="两贵引从天干格";