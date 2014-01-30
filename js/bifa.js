//毕法
var b={
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
		if(g.sanChuan[0]-2==g.sanChuan[2] || g.sanChuan[2]-g.sanChuan[0]==10){
			this.data.push(0);
			//引干
			if(g.sanChuan[0]-1==g.siKe[1] || g.sanChuan[2]+1==g.siKe[1]){
				this.data.push(1);
				//拱贵
				if(g.siKeTianJiang[0]==0){
					this.data.push(3);
				}
				//两贵引从天干格
				if( (g.sanChuan[0]==5 && g.sanChuan[2]==3) || (g.sanChuan[0]==11 && g.sanChuan[2]==9) ){
					//壬癸蛇兔藏 丙丁猪鸡位
					this.data.push(4);
				}
			}
			//引支
			if(g.sanChuan[0]-1==g.siKe[5] || g.sanChuan[2]+1==g.siKe[5]){
				this.data.push(2);
			}
		}
	}
};

var bStr=[];
bStr[0]="前后引从升迁吉";
bStr[1]="引干宜进职";
bStr[2]="引支宜迁宅";
bStr[3]="拱贵格";
bStr[4]="两贵引从天干格";