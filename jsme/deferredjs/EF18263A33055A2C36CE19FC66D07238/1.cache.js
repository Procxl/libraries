var $O="3",aP="Any",bP="Aromatic",cP="Nonring",dP="Reset",eP="Ring";function fP(){fP=r;gP=new Vo(Uc,new hP)}function hP(){}q(210,207,{},hP);_.Pc=function(a){mx();FL(this,a.b,iP(a.a.a,a.a.a.ob.selectedIndex))};_.Sc=function(){return gP};var gP;function jP(a,b){if(0>b||b>=a.ob.options.length)throw new ou;}function iP(a,b){jP(a,b);return a.ob.options[b].value}function kP(){this.ob=$doc.createElement("select");this.ob[cd]="gwt-ListBox"}q(356,334,qh,kP);function lP(){lP=r}
function mP(a,b){if(null==b)throw new Nq("Missing message: awt.103");var c=-1,d,e,f;f=a.mc.a.ob;e=$doc.createElement(mf);e.text=b;e.removeAttribute("bidiwrapped");e.value=b;d=f.options.length;(0>c||c>d)&&(c=d);c==d?f.add(e,null):(c=f.options[c],f.add(e,c))}function nP(){lP();lx.call(this);new wi;this.mc=new oP((mx(),this))}q(421,408,{90:1,92:1,99:1,111:1,117:1},nP);_.ae=function(){return qx(this.mc,this)};
_.pe=function(){return(null==this.jc&&(this.jc=Yw(this)),this.jc)+xa+this.uc+xa+this.vc+xa+this.rc+Jg+this.hc+(this.qc?l:",hidden")+",current="+iP(this.mc.a,this.mc.a.ob.selectedIndex)};function pP(){dL.call(this,7)}q(434,1,Wh,pP);function sP(a){fL.call(this,a,0)}q(439,408,wh,sP);function tP(a){var b=a.j;EL(a.mc.c,b.a,b.b);!$w(a)&&yK(a);tK(a)}
function uP(a,b,c){BL.call(this);this.mc&&ZK(this.mc.c,!1);WK(this,!1);zx(this,new dL(0));a=new fL(a,1);$(this,a,null);a=new Ex;$(a,this.i,null);$(this,a,null);b&&(this.j=ax(b),VK(this),AL(this.j,~~(G(b.$b.ob,jf)/2)-~~(this.rc/2),~~(G(b.$b.ob,hf)/2)-~~(this.hc/2)));c&&Z(this,c)}q(567,568,oH,uP);_.jg=function(){return"OK"};q(573,574,xh);_.Lc=function(){tP(new uP(this.b,this.a,(sA(),uA)))};q(576,574,xh);_.Lc=function(){!this.a.Ib?this.a.Ib=new vP(this.a):this.a.Ib.mc.c.gb?sM(this.a.Ib.mc.c):tP(this.a.Ib)};
function wP(a,b){pK(b)==a.a?Z(b,(Xx(),fy)):Z(b,a.a)}
function xP(a){var b,c,d,e;e=l;d=!1;pK(yP)!=a.a?(e=va,d=!0):pK(zP)!=a.a?(e="!#6",d=!0):pK(AP)!=a.a?(Z(BP,(Xx(),fy)),Z(CP,fy),Z(DP,fy),Z(EP,fy),e="F,Cl,Br,I"):(b=pK(FP)!=a.a,c=pK(GP)!=a.a,pK(HP)!=a.a&&(b?e+="c,":c?e+="C,":e+="#6,"),pK(IP)!=a.a&&(b?e+="n,":c?e+="N,":e+="#7,"),pK(JP)!=a.a&&(b?e+="o,":c?e+="O,":e+="#8,"),pK(KP)!=a.a&&(b?e+="s,":c?e+="S,":e+="#16,"),pK(LP)!=a.a&&(b?e+="p,":c?e+="P,":e+="#15,"),pK(BP)!=a.a&&(e+="F,"),pK(CP)!=a.a&&(e+="Cl,"),pK(DP)!=a.a&&(e+="Br,"),pK(EP)!=a.a&&(e+="I,"),
nE(e,xa)&&(e=e.substr(0,e.length-1-0)),1>e.length&&!a.b&&(b?e=oc:c?e=qb:(Z(yP,(Xx(),fy)),e=va)));b=l;d&&pK(FP)!=a.a&&(b+=";a");d&&pK(GP)!=a.a&&(b+=";A");pK(MP)!=a.a&&(b+=";R");pK(NP)!=a.a&&(b+=";!R");pK(yP)!=a.a&&0<b.length?e=b.substr(1,b.length-1):e+=b;d=OP.mc.a.ob.selectedIndex;0<d&&(--d,e+=";H"+d);d=PP.mc.a.ob.selectedIndex;0<d&&(--d,e+=";D"+d);pK(QP)!=a.a&&(e="~");pK(RP)!=a.a&&(e=gb);pK(SP)!=a.a&&(e=pb);pK(TP)!=a.a&&(e="!@");iL(a.e,e)}
function UP(a){VP(a);WP(a);var b=OP.mc.a;jP(b,0);b.ob.options[0].selected=!0;b=PP.mc.a;jP(b,0);b.ob.options[0].selected=!0;Z(FP,a.a);Z(GP,a.a);Z(MP,a.a);Z(NP,a.a);Z(OP,a.a);Z(PP,a.a);XP(a)}function VP(a){Z(HP,a.a);Z(IP,a.a);Z(JP,a.a);Z(KP,a.a);Z(LP,a.a);Z(BP,a.a);Z(CP,a.a);Z(DP,a.a);Z(EP,a.a)}function WP(a){Z(yP,a.a);Z(zP,a.a);Z(AP,a.a)}function XP(a){Z(QP,a.a);Z(RP,a.a);Z(SP,a.a);Z(TP,a.a);a.b=!1}
function vP(a){XK.call(this,"Atom/Bond Query");this.i=new QK(this.jg());Qx(this.q,new CL(this));this.a=(sA(),uA);this.c=a;this.d||(a=ax(a),this.d=new hL(a),AL(this.d,-150,10));this.j=this.d;zx(this,new pP);Z(this,this.a);a=new Ex;zx(a,new wy(0,3,1));$(a,new sP("Atom type :"),null);yP=new QK(aP);zP=new QK("Any except C");AP=new QK("Halogen");$(a,yP,null);$(a,zP,null);$(a,AP,null);$(this,a,null);a=new Ex;zx(a,new wy(0,3,1));$(a,new fL("Or select one or more from the list :",0),null);$(this,a,null);
a=new Ex;zx(a,new wy(0,3,1));HP=new QK(tb);IP=new QK(Pb);JP=new QK(Tb);KP=new QK($b);LP=new QK(Ub);BP=new QK(Cb);CP=new QK(yb);DP=new QK(sb);EP=new QK(Ib);$(a,HP,null);$(a,IP,null);$(a,JP,null);$(a,KP,null);$(a,LP,null);$(a,BP,null);$(a,CP,null);$(a,DP,null);$(a,EP,null);$(this,a,null);a=new Ex;zx(a,new wy(0,3,1));OP=new nP;mP(OP,aP);mP(OP,$a);mP(OP,db);mP(OP,fb);mP(OP,$O);$(a,new sP("Number of hydrogens :  "),null);$(a,OP,null);$(this,a,null);a=new Ex;zx(a,new wy(0,3,1));PP=new nP;mP(PP,aP);mP(PP,
$a);mP(PP,db);mP(PP,fb);mP(PP,$O);mP(PP,"4");mP(PP,"5");mP(PP,"6");$(a,new fL("Number of connections :",0),null);$(a,PP,null);$(a,new fL(" (H's don't count.)",0),null);$(this,a,null);a=new Ex;zx(a,new wy(0,3,1));$(a,new sP("Atom is :"),null);FP=new QK(bP);$(a,FP,null);GP=new QK("Nonaromatic");$(a,GP,null);MP=new QK(eP);$(a,MP,null);NP=new QK(cP);$(a,NP,null);$(this,a,null);a=new Ex;Z(a,my(pK(this)));zx(a,new wy(0,3,1));$(a,new sP("Bond is :"),null);QP=new QK(aP);$(a,QP,null);RP=new QK(bP);$(a,RP,
null);SP=new QK(eP);$(a,SP,null);TP=new QK(cP);$(a,TP,null);$(this,a,null);a=new Ex;zx(a,new wy(1,3,1));this.e=new bz(va,20);$(a,this.e,null);$(a,new QK(dP),null);$(a,this.i,null);$(this,a,null);this.mc&&ZK(this.mc.c,!1);WK(this,!1);VP(this);WP(this);XP(this);Z(FP,this.a);Z(GP,this.a);Z(MP,this.a);Z(NP,this.a);Z(OP,this.a);Z(PP,this.a);wP(this,yP);VK(this);a=this.j;EL(this.mc.c,a.a,a.b);!$w(this)&&yK(this);tK(this)}q(586,568,oH,vP);
_.kg=function(a,b){var c;H(b,dP)?(UP(this),wP(this,yP),xP(this)):D(a.f,89)?(XP(this),ur(a.f)===ur(yP)?(VP(this),WP(this)):ur(a.f)===ur(zP)?(VP(this),WP(this)):ur(a.f)===ur(AP)?(VP(this),WP(this)):ur(a.f)===ur(MP)?Z(NP,this.a):ur(a.f)===ur(NP)?(Z(MP,this.a),Z(FP,this.a)):ur(a.f)===ur(FP)?(Z(GP,this.a),Z(NP,this.a)):ur(a.f)===ur(GP)?Z(FP,this.a):ur(a.f)===ur(QP)||ur(a.f)===ur(RP)||ur(a.f)===ur(SP)||ur(a.f)===ur(TP)?(UP(this),this.b=!0):WP(this),wP(this,a.f),xP(this)):D(a.f,90)&&(XP(this),c=a.f,0==c.mc.a.ob.selectedIndex?
Z(c,this.a):Z(c,(Xx(),fy)),xP(this));107!=this.c.e&&(this.c.e=107,Jx(this.c));return!0};_.lg=function(){return Em(this.e.mc.a.ob,Dg)};_.mg=function(){return this.b};_.b=!1;_.c=null;_.d=null;var yP=_.e=null,QP=null,zP=null,FP=null,RP=null,DP=null,HP=null,PP=null,OP=null,CP=null,BP=null,AP=null,EP=null,IP=null,GP=null,NP=null,TP=null,JP=null,LP=null,MP=null,SP=null,KP=null;function oP(a){wG();yG.call(this);this.a=new kP;Wt(this.a,new YP(this,a),(fP(),fP(),gP))}q(632,630,{},oP);_.Je=function(){return this.a};
_.a=null;function YP(a,b){this.a=a;this.b=b}q(633,1,{},YP);_.a=null;_.b=null;X(567);X(586);X(421);X(632);X(633);X(356);X(210);x(nH)(1);