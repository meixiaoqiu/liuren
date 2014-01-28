//毕法
var b=[];
function biFa(){
	//前后引从升迁吉
	if(g.sanChuan[0]-2==g.sanChuan[2] || g.sanChuan[2]-g.sanChuan[0]==10){
		b.push(0);
		//引干
		if(g.sanChuan[0]-1==g.siKe[1] || g.sanChuan[2]+1==g.siKe[1]){
			b.push(1);
		}
		//引支
		if(g.sanChuan[0]-1==g.siKe[5] || g.sanChuan[2]+1==g.siKe[5]){
			b.push(2);
		}
	}
}

var bStr=[];
bStr[0]="前后引从升迁吉";
bStr[1]="引干宜进职";
bStr[2]="引支宜迁宅";