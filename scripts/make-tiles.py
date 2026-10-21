import random
W,H=640,360
def rect(x,y,w,h,f,r=0,o=1,extra=''): return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{f}" rx="{r}" opacity="{o}" {extra}/>'
def circ(cx,cy,r,f,o=1,extra=''): return f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{f}" opacity="{o}" {extra}/>'
def ell(cx,cy,rx,ry,f,o=1,extra=''): return f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="{f}" opacity="{o}" {extra}/>'
def poly(pts,f,o=1,extra=''): return f'<polygon points="{" ".join(f"{x},{y}" for x,y in pts)}" fill="{f}" opacity="{o}" {extra}/>'
def path(d,f='none',s='none',w=2,o=1,extra=''): return f'<path d="{d}" fill="{f}" stroke="{s}" stroke-width="{w}" stroke-linecap="round" stroke-linejoin="round" opacity="{o}" {extra}/>'
def line(x1,y1,x2,y2,s,w=3,o=1,extra=''): return f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{s}" stroke-width="{w}" stroke-linecap="round" opacity="{o}" {extra}/>'
def text(x,y,t,c,size=24,fam="monospace",o=1,extra=''): return f'<text x="{x}" y="{y}" font-family="{fam}" font-weight="bold" font-size="{size}" fill="{c}" opacity="{o}" {extra}>{t}</text>'
def g(content,extra=''): return f'<g {extra}>{content}</g>'
GLOW='filter="url(#glow)"'; SOFT='filter="url(#soft)"'; SHADOW='filter="url(#shadow)"'
def stars(seed,n=60,c="#fff",maxy=H-80):
    r=random.Random(seed); out=""
    for _ in range(n):
        x,y=r.randint(0,W),r.randint(0,maxy); s=r.choice([1,1,1,1.5,2,2.5]); o=r.choice([.25,.4,.6,.9])
        out+=circ(x,y,s,c,o)
        if s>=2: out+=circ(x,y,s*3,c,.12)
    return out
def grid(c,step=40,o=.08): return "".join(line(x,0,x,H,c,1,o) for x in range(0,W,step))+"".join(line(0,y,W,y,c,1,o) for y in range(0,H,step))
def cube(x,y,s,top,left,right):
    # isometric-ish block with three faces
    return poly([(x,y),(x+s,y),(x+s,y+s),(x,y+s)],left)+poly([(x+s,y),(x+s+s*.35,y-s*.35),(x+s+s*.35,y+s-s*.35),(x+s,y+s)],right)+poly([(x,y),(x+s*.35,y-s*.35),(x+s+s*.35,y-s*.35),(x+s,y)],top)
def hills(seed,c,base,amp,o=1):
    r=random.Random(seed); pts=[(0,H)]; x=0; y=base
    while x<=W:
        pts.append((x,y)); x+=r.randint(50,110); y=base+r.randint(-amp,amp)
    pts.append((W,base)); pts.append((W,H))
    return poly(pts,c,o)
def invader(x,y,c,s=9):
    m=["..X.....X..","...X...X...","..XXXXXXX..",".XX.XXX.XX.","XXXXXXXXXXX","X.XXXXXXX.X","X.X.....X.X","...XX.XX..."]
    return "".join(rect(x+j*s,y+i*s,s-1,s-1,c) for i,row in enumerate(m) for j,ch in enumerate(row) if ch=='X')
def ghost(x,y,c,r=22):
    d=f"M {x-r} {y+r} L {x-r} {y} A {r} {r} 0 0 1 {x+r} {y} L {x+r} {y+r} L {x+r*.66} {y+r*.75} L {x+r*.33} {y+r} L {x} {y+r*.75} L {x-r*.33} {y+r} L {x-r*.66} {y+r*.75} Z"
    return path(d,c)+circ(x-r*.38,y-r*.05,r*.3,'#fff')+circ(x+r*.38,y-r*.05,r*.3,'#fff')+circ(x-r*.3,y,r*.15,'#1a1aff')+circ(x+r*.46,y,r*.15,'#1a1aff')
def card(x,y,w,h,face,back=False):
    return rect(x+4,y+6,w,h,'#000',10,.35)+rect(x,y,w,h,face,10)+(rect(x+8,y+8,w-16,h-16,'none',6,1,'stroke="#fff" stroke-opacity=".35" stroke-width="2" stroke-dasharray="4 4"') if back else '')
def scanlines(): return '<rect width="640" height="360" fill="url(#scan)" opacity=".18"/>'
def vignette(): return '<rect width="640" height="360" fill="url(#vig)"/>'

games={}
# ---------- Chopper ----------
games['chopper']=('#071226','#153a6b','#00d1ff',
  stars(1,70,maxy=220)+circ(540,70,34,'#ffe9a8',.95,GLOW)+circ(530,62,8,'#ffd27a',.5)
  +hills(11,'#0d1f40',230,40)+hills(12,'#0a1832',270,30)+hills(13,'#060f22',300,20)
  +"".join(rect(x,y,w,h,'#000',0,.5) for (x,y,w,h) in [(60,250,30,110),(100,270,20,90),(500,255,36,105),(560,275,24,85)])
  +"".join(rect(x,y,4,4,'#ffd27a',0,.9) for (x,y) in [(68,262),(80,262),(68,280),(508,266),(520,266),(520,284),(568,285)])
  +circ(300,120,120,'#00d1ff',.08,SOFT)
  +g(
     # tail boom and fin
     path("M 340 178 L 455 166 L 455 184 L 340 196 Z",'#e6a93a')+path("M 448 150 L 470 142 L 470 186 L 448 186 Z",'#ffc857')+circ(470,164,16,'none',1,'stroke="#dbe6ff" stroke-width="3"')+line(454,164,486,164,'#dbe6ff',3)+line(470,148,470,180,'#dbe6ff',3)
     # body
     +path("M 230 186 C 230 160 262 150 300 150 L 350 150 C 372 150 380 170 380 186 C 380 204 364 212 340 212 L 262 212 C 244 212 230 202 230 186 Z",'#ffc857')
     +path("M 232 190 L 378 190 L 376 200 C 372 210 360 212 340 212 L 262 212 C 246 212 236 204 232 190 Z",'#e6a93a')
     # cockpit glass
     +path("M 236 184 C 240 166 262 156 296 156 L 300 182 Z",'#8ee8ff',.95)+path("M 244 176 C 250 168 262 162 280 160 L 282 170 Z",'#fff',.6)
     # window and door
     +rect(314,160,24,18,'#8ee8ff',4,.9)+line(348,158,348,206,'#d89a2c',2,.7)
     # mast and rotor
     +rect(302,136,10,16,'#1a2338',2)+ell(307,134,120,5,'#dbe6ff',.85)+ell(307,134,120,10,'#dbe6ff',.18)
     # skids
     +line(252,226,352,226,'#1a2338',5)+line(270,212,266,226,'#1a2338',4)+line(336,212,340,226,'#1a2338',4)
     # nav lights
     +circ(232,186,3,'#ff3b81',1,GLOW)+circ(486,164,3,'#3ddc97',1,GLOW)
     ,SHADOW)
  # rescue line and survivor
  +line(300,228,296,300,'#dbe6ff',2,.8)+g(circ(296,296,8,'#ffd1a0')+rect(290,304,12,18,'#ff3b81',3)+circ(296,296,16,'#ff3b81',.25,GLOW))
  # searchlight cone
  +poly([(262,226),(200,330),(330,330)],'#ffe9a8',.14))
# ---------- Blockworld ----------
sky=circ(540,80,40,'#fff2b0',1,GLOW)
clouds="".join(ell(x,y,w,h,'#fff',.9) for (x,y,w,h) in [(120,80,60,18),(150,70,40,20),(420,60,70,16),(455,52,40,18)])
layers=""
r=random.Random(3)
for row in range(6):
    y=330-row*30
    for col in range(-1,18):
        x=col*42+(row%2)*21
        depth=r.random()
        if row<2: layers+=cube(x,y,30,'#6cb043','#4a8a30','#3b6f26')
        elif row<4 and r.random()<.85: layers+=cube(x,y,30,'#8b6a3e','#6b4e2b','#54391e')
        elif r.random()<.3: layers+=cube(x,y,30,'#9aa5ad','#737d85','#565d63')
tree_x,tree_y=300,210
tree=cube(tree_x,tree_y+30,30,'#8b5a2b','#6b4423','#4d2f17')+cube(tree_x,tree_y,30,'#8b5a2b','#6b4423','#4d2f17')+"".join(cube(tree_x+dx,tree_y-60+dy,30,'#7ac943','#56a02e','#3f7a21') for (dx,dy) in [(-30,30),(30,30),(0,30),(-30,0),(30,0),(0,0),(0,-30)])
games['blockworld']=('#3d8ddb','#9fd8ff','#7ac943', sky+clouds+hills(21,'#5fae6a',250,24,.6)+layers+tree
  +cube(120,270,30,'#c23b3b','#8f2b2b','#6b1f1f')+cube(150,270,30,'#c23b3b','#8f2b2b','#6b1f1f')+cube(135,240,30,'#eee','#bbb','#999'))
# ---------- Towers ----------
pathd="M 0 150 L 180 150 L 180 250 L 420 250 L 420 120 L 640 120"
towers=""
for (x,y) in [(110,90),(250,200),(360,180),(480,60),(540,180),(80,300),(300,300)]:
    towers+=g(rect(x-18,y-10,36,36,'#2b2b45',6)+rect(x-14,y-14,28,10,'#ffc857',3)+circ(x,y+8,9,'#00d1ff',1,GLOW)+rect(x-4,y-30,8,22,'#9aa0a6',2),SHADOW)
enemies="".join(g(circ(x,y,10,'#ff3b81',1,GLOW)+circ(x-3,y-3,3,'#fff',.8)) for (x,y) in [(30,150),(75,150),(130,150),(180,190),(180,235),(240,250),(300,250)])
beams="".join(line(x,y+8,tx,ty,'#00d1ff',2,.8,GLOW) for (x,y,tx,ty) in [(110,90,130,150),(250,200,240,250),(360,180,300,250)])
games['towers']=('#1b1030','#2e1a4e','#ff3b81', grid('#ff3b81',40,.12)+path(pathd,'none','#4a3a70',40)+path(pathd,'none','#6c56a3',34)+path(pathd,'none','#3a2b5e',2,.5,'stroke-dasharray="8 8"')+towers+enemies+beams+rect(560,120,50,40,'#2b2b45',4)+rect(568,100,34,24,'#ffc857',3)+text(573,118,'HQ','#1b1030',16,'ui-sans-serif,system-ui'))
# ---------- Dungeon ----------
r=random.Random(7); floor=""
for y in range(0,320,40):
    for x in range(0,W,40):
        shade=['#241b3a','#2a2042','#1f1733'][r.randint(0,2)]; floor+=rect(x,y,40,40,shade)+rect(x,y,40,40,'none',0,1,'stroke="#15102a" stroke-width="2"')
torchlight=circ(320,190,200,'#ff8c42',.18,SOFT)+circ(320,190,110,'#ffb060',.18,SOFT)
hero=g(rect(300,150,40,64,'#2c2c3a',6)+rect(294,140,52,16,'#50506a',4)+circ(320,134,14,'#f0c7a0')+rect(306,120,28,10,'#3a3a4a',3)+rect(312,160,16,30,'#c0392b',2)+rect(352,150,8,60,'#d8d8e8',2)+poly([(356,140),(366,156),(346,156)],'#eee')+rect(282,170,12,30,'#8b5a2b',2),SHADOW)
chest=g(rect(455,246,70,44,'#8b5a2b',6)+rect(455,246,70,16,'#a8713a',6)+rect(482,258,16,16,'#ffc857',3)+"".join(circ(x,y,3,'#ffd27a',1,GLOW) for (x,y) in [(470,236),(500,232),(515,240)]),SHADOW)
skel=g(circ(130,200,14,'#e8e8e8')+rect(122,214,16,34,'#d8d8d8',3)+circ(125,197,3,'#000')+circ(135,197,3,'#000')+line(112,222,150,222,'#d8d8d8',4),SHADOW)
games['dungeon']=('#120d1f','#1f1733','#ff8c42', floor+torchlight+"".join(rect(x,0,W,0,'') for x in [])+rect(40,40,16,60,'#3a2d55',4)+circ(48,36,10,'#ff8c42',1,GLOW)+rect(584,40,16,60,'#3a2d55',4)+circ(592,36,10,'#ff8c42',1,GLOW)+hero+chest+skel)
# ---------- Space Invaders ----------
inv=""
for row,(c,y) in enumerate([('#3ddc97',40),('#3ddc97',110),('#ff3b81',180)]):
    for col in range(5):
        inv+=g(invader(90+col*100,y,c,8),GLOW)
ship=g(poly([(290,290),(350,290),(350,276),(338,276),(338,266),(326,266),(326,258),(314,258),(314,266),(302,266),(302,276),(290,276)],'#00d1ff',1,GLOW)+rect(318,230,4,20,'#fff',0,1,GLOW))
bunkers="".join(rect(x,245,44,18,'#3ddc97',3,.75)+rect(x+14,255,16,8,'#000') for x in [100,230,420,540])
games['space-invaders']=('#000','#070720','#3ddc97', stars(5,80)+inv+bunkers+ship+line(0,330,W,330,'#3ddc97',2,.6)+text(24,32,'SCORE 004120','#eee',16)+text(520,32,'LIVES 3','#eee',16))
# ---------- Missile Command ----------
mc=stars(3,50,maxy=260)
for (x1,x2) in [(40,160),(220,330),(400,520),(520,600)]: mc+=line(x1,0,x2,300,'#ff3b81',2,.9,GLOW)
for (tx,ty) in [(150,170),(470,140)]: mc+=line(320,328,tx,ty,'#00d1ff',2,.8)
mc+=circ(150,170,26,'#00d1ff',.55,GLOW)+circ(150,170,12,'#fff',.8)+circ(470,140,36,'#00d1ff',.35,GLOW)+circ(470,140,18,'#fff',.5)
mc+=hills(31,'#2a2a3a',322,0)+"".join(g(rect(x,300,12,26,'#3ddc97')+rect(x+16,290,10,36,'#3ddc97')+rect(x+30,306,14,20,'#3ddc97'),GLOW) for x in [60,180,420,540])
mc+=poly([(290,330),(350,330),(320,290)],'#ffc857')+rect(0,328,W,32,'#2a2a3a')+text(24,32,'1990  ×3','#eee',16)
games['missile-command']=('#09091a','#1a1030','#ff3b81', mc)
# ---------- Asteroids ----------
ast=stars(9,90)
for p in ["120,80 170,60 210,90 200,140 150,160 110,130","440,200 500,180 540,220 520,270 460,280 420,240","80,250 110,240 120,270 90,285","560,70 590,60 600,90 580,100"]:
    ast+=f'<polygon points="{p}" fill="#0a0a1a" stroke="#fff" stroke-width="3" filter="url(#glow)"/>'
ast+=path("M 320 150 L 342 206 L 320 194 L 298 206 Z",'#0a0a1a','#fff',3,1,GLOW)+path("M 310 204 L 320 224 L 330 204",'none','#ffc857',3,1,GLOW)
ast+=circ(332,120,2.5,'#fff',1,GLOW)+circ(348,92,2.5,'#fff',1,GLOW)+text(24,32,'00450','#fff',20)+"".join(path(f"M {x} 20 L {x+8} 36 L {x} 32 L {x-8} 36 Z",'none','#fff',2) for x in [560,585,610])
games['asteroids']=('#000','#0a0a1a','#fff', ast)
# ---------- Pac-Man ----------
maze=rect(20,110,600,140,'none',14,1,'stroke="#2a2aff" stroke-width="6"')+rect(28,118,584,124,'none',10,1,'stroke="#2a2aff" stroke-width="3" stroke-opacity=".6"')+rect(260,150,120,60,'none',8,1,'stroke="#2a2aff" stroke-width="5"')
dots="".join(circ(x,180,4,'#ffe0a0') for x in range(70,600,36) if not 230<x<420)+circ(70,135,9,'#ffe0a0',1,GLOW)+circ(570,225,9,'#ffe0a0',1,GLOW)
pac=path("M 200 180 L 236 156 A 40 40 0 1 0 236 204 Z",'#ffc857','none',2,1,GLOW)+circ(206,166,4,'#000')
games['pacman']=('#000','#08082a','#ffc857', maze+dots+pac+"".join(g(ghost(x,178,c),GLOW) for (x,c) in [(400,'#ff3b81'),(458,'#00d1ff'),(516,'#ff8c42')])+text(24,60,'HIGH SCORE  12340','#eee',16)+rect(0,280,W,80,'none',0,1))
# ---------- Tetris ----------
well=rect(220,0,200,320,'#0a0a2a',0,.85)+rect(220,0,200,320,'none',0,1,'stroke="#3a3a6a" stroke-width="3"')+grid('#fff',20,.05)
cells=[]
rows=["XXX.XXXXXX","XXXXXX.XXX","XX.XXXXXXX",".XXXX.XXXX"]
cols=['#00d1ff','#ffc857','#ff3b81','#7ac943','#9b59b6','#ff8c42']
r=random.Random(2)
for i,row in enumerate(rows):
    y=300-i*20
    for j,ch in enumerate(row):
        if ch=='X': cells.append(rect(222+j*20,y,18,18,r.choice(cols),2)+rect(222+j*20,y,18,4,'#fff',0,.35))
falling="".join(rect(282+dx,120+dy,18,18,'#ff8c42',2)+rect(282+dx,120+dy,18,4,'#fff',0,.35) for (dx,dy) in [(0,0),(0,20),(0,40),(20,40)])
nxt=rect(450,40,100,80,'#0a0a2a',8,.85)+rect(450,40,100,80,'none',8,1,'stroke="#3a3a6a" stroke-width="2"')+text(462,62,'NEXT','#9aa7c7',14)+"".join(rect(470+dx,76+dy,18,18,'#00d1ff',2) for (dx,dy) in [(0,0),(20,0),(40,0),(60,0)])
games['tetris']=('#0b1020','#1a1140','#00d1ff', well+"".join(cells)+g(falling,GLOW)+nxt+text(80,62,'SCORE','#9aa7c7',14)+text(80,92,'38200','#fff',24)+text(80,140,'LEVEL','#9aa7c7',14)+text(80,170,'7','#fff',24))
# ---------- Snake ----------
body=[(130,98),(162,98),(194,98),(226,98),(258,98),(258,130),(258,162),(258,194),(290,194),(322,194),(354,194),(354,226)]
sn="".join(rect(x,y,28,28,'#5fb635',7)+rect(x+4,y+4,20,8,'#8ee05a',4,.5) for (x,y) in body)
sn+=rect(354,258,28,28,'#3ddc97',7,1,GLOW)+circ(362,266,4,'#111')+circ(374,266,4,'#111')+path("M 368 286 L 368 296 L 364 300 M 368 296 L 372 300",'none','#ff3b81',2)
apple=g(circ(500,112,13,'#ff3b81',1,GLOW)+circ(495,107,4,'#fff',.5)+rect(498,94,4,9,'#5a9e3a')+ell(506,97,6,3,'#7ac943'))
games['snake']=('#0a2a18','#0f3d25','#7ac943', grid('#7ac943',32,.12)+rect(2,2,W-4,H-44,'none',6,1,'stroke="#7ac943" stroke-opacity=".4" stroke-width="3"')+sn+apple+text(24,32,'SCORE 12','#7ac943',16))
# ---------- Pong ----------
games['pong']=('#000','#111','#fff', "".join(rect(317,y,6,16,'#fff',0,.5) for y in range(0,320,32))+rect(40,110,12,80,'#fff',3,1,GLOW)+rect(588,160,12,80,'#fff',3,1,GLOW)+"".join(rect(380-i*26,140+i*12,14,14,'#fff',2,.08*(6-i)) for i in range(1,6))+rect(380,140,14,14,'#fff',2,1,GLOW)+text(240,70,'3','#fff',48,'monospace',.8)+text(380,70,'5','#fff',48,'monospace',.8))
# ---------- Reversi ----------
rv=rect(154,-6,332,332,'#1a4a2e',8)+"".join(rect(x,y,38,38,'#2e8b57' if (x//40+y//40)%2 else '#27794a') for x in range(160,480,40) for y in range(0,320,40))
pieces=[(280,120,'#111'),(320,120,'#eee'),(280,160,'#eee'),(320,160,'#111'),(240,160,'#111'),(360,120,'#111'),(320,200,'#eee'),(360,200,'#eee'),(240,120,'#eee'),(200,160,'#eee'),(400,160,'#111')]
rv+="".join(circ(x+21,y+22,15,'#000',.4)+circ(x+20,y+20,15,c)+circ(x+15,y+15,5,'#fff' if c=='#eee' else '#555',.5) for (x,y,c) in pieces)
rv+=circ(420,100,15,'#ffc857',.45,GLOW)+circ(180,220,15,'#ffc857',.45,GLOW)
games['reversi']=('#0a2e1c','#1b5e3a','#ffc857', rv)
# ---------- Minesweeper ----------
ms=rect(154,-6,332,332,'#9a9aa8',4)
r=random.Random(4)
revealed={(200,80):1,(240,80):2,(280,120):3,(320,160):1,(200,200):2,(360,240):1,(240,120):0,(200,120):0,(200,160):0,(240,160):0}
for x in range(160,480,40):
    for y in range(0,320,40):
        if (x,y) in revealed: ms+=rect(x,y,38,38,'#c6c6d2',2)+rect(x,y,38,38,'none',2,1,'stroke="#8a8a98" stroke-width="1"')
        else: ms+=rect(x,y,38,38,'#e4e4ec',3)+rect(x,y,38,4,'#fff',0,.8)+rect(x,y+34,38,4,'#8a8a98',0,.8)+rect(x+34,y,4,38,'#8a8a98',0,.8)
for (x,y),n in revealed.items():
    if n: ms+=text(x+11,y+28,n,{1:'#1a3dff',2:'#0a7a0a',3:'#d00'}[n],24)
ms+=circ(418,218,13,'#111')+"".join(line(418,218,418+dx,218+dy,'#111',3) for (dx,dy) in [(0,-18),(0,18),(-18,0),(18,0),(13,-13),(-13,13),(13,13),(-13,-13)])+circ(413,213,4,'#fff',.6)
ms+="".join(rect(x+16,y+8,3,22,'#111')+poly([(x+16,y+8),(x+6,y+14),(x+16,y+20)],'#ff3b81') for (x,y) in [(280,160),(400,80)])
ms+=rect(20,20,110,48,'#000',6)+text(30,54,'010','#ff2020',30,'monospace',1,GLOW)+rect(510,20,110,48,'#000',6)+text(520,54,'047','#ff2020',30,'monospace',1,GLOW)+circ(320,-100,0,'#000')
games['minesweeper']=('#2b2b3a','#3a3a4a','#ff3b81', ms)
# ---------- Candy Rush ----------
cr=""
r=random.Random(8); cols=['#ff3b81','#ffc857','#7ac943','#9b59b6','#00d1ff','#ff8c42']
for y in range(20,320,60):
    for x in range(140,500,60):
        c=r.choice(cols); cr+=rect(x,y,56,56,'#fff',14,.35)
        kind=r.randint(0,2)
        if kind==0: cr+=circ(x+28,y+28,20,c)+circ(x+21,y+21,7,'#fff',.6)
        elif kind==1: cr+=rect(x+8,y+8,40,40,c,10,1,'transform="rotate(45 %d %d)"'%(x+28,y+28))+circ(x+22,y+22,6,'#fff',.5)
        else: cr+=ell(x+28,y+28,22,14,c)+poly([(x+6,y+14),(x+14,y+28),(x+6,y+42)],c)+poly([(x+50,y+14),(x+42,y+28),(x+50,y+42)],c)+ell(x+22,y+24,7,4,'#fff',.6)
cr+=rect(260,80,176,56,'none',16,1,'stroke="#fff" stroke-width="5" filter="url(#glow)"')+text(24,52,'MOVES 12','#fff',20,'ui-sans-serif,system-ui')+text(520,52,'★ 5,400','#fff',20,'ui-sans-serif,system-ui')
games['candy']=('#ff7fbf','#ffd1e8','#ff3b81', cr)
# ---------- Space Huggers ----------
sh=stars(7,70)+circ(560,70,30,'#ffc857',1,GLOW)+hills(41,'#2a1a3e',260,30)+rect(0,300,W,60,'#1d1030')+"".join(rect(x,y,w,h,'#2d1d44',4) for (x,y,w,h) in [(100,240,120,60),(380,200,160,100),(560,230,80,70)])
sh+=g(rect(150,196,28,44,'#ff8c42',6)+circ(164,184,13,'#ffd1a0')+rect(154,174,22,8,'#ff8c42',3)+rect(178,212,34,8,'#9aa0a6',2)+rect(156,240,8,20,'#333')+rect(166,240,8,20,'#333'),SHADOW)
sh+="".join(circ(x,216,3,'#ffc857',1,GLOW) for x in [225,250,275])
sh+="".join(g(rect(x,y,24,36,'#3ddc97',6)+circ(x+12,y-6,11,'#3ddc97')+circ(x+8,y-8,3,'#000')+circ(x+16,y-8,3,'#000')+rect(x-6,y+8,8,16,'#3ddc97',3)+rect(x+22,y+8,8,16,'#3ddc97',3),SHADOW) for (x,y) in [(420,164),(470,164),(590,194)])
sh+=circ(300,120,40,'#ff3b81',.12,SOFT)+text(24,32,'HP ████░','#ff8c42',16)
games['space-huggers']=('#160a28','#3a1a4e','#ff8c42', sh)
# ---------- MiniCiv ----------
mc2=""
r=random.Random(12)
terr=['#2d6a4f','#3a8a5f','#5a9e3a','#8b5a2b','#1b4b7a','#4a7ac0','#6aa86a']
for b in range(0,24):
    for a in range(-1,17):
        x,y=a*40+(b%2)*20,b*12+30
        c=terr[(a*3+b*5+r.randint(0,1))%7]
        mc2+=poly([(x,y+10),(x+20,y),(x+40,y+10),(x+20,y+20)],c)+poly([(x,y+10),(x+20,y+20),(x+20,y+26),(x,y+16)],'#000',.25)
for (x,y,c) in [(230,140,'#ffc857'),(262,120,'#ffc857'),(400,180,'#ff3b81'),(330,210,'#ffc857'),(470,100,'#ff3b81')]:
    mc2+=g(poly([(x,y+8),(x+14,y),(x+28,y+8),(x+14,y+16)],'#d8c090')+rect(x+4,y-14,20,18,'#e8d7b0')+poly([(x+2,y-14),(x+14,y-26),(x+26,y-14)],c),SHADOW)
mc2+=rect(500,70,6,34,'#ffc857')+poly([(506,70),(534,78),(506,86)],'#ff3b81')+rect(20,20,150,30,'#000',6,.5)+text(30,41,'TURN 42  ⚑ 3 cities','#fff',14,'ui-sans-serif,system-ui')
games['miniciv']=('#1b3a5c','#2d6a4f','#ffc857', mc2)
# ---------- Dwarfs ----------
dw=""
r=random.Random(15)
for y in range(0,320,40):
    for x in range(0,W,40):
        dw+=rect(x,y,40,40,['#4a3220','#5a3e2b','#3f2a18'][r.randint(0,2)])+rect(x,y,40,40,'none',0,1,'stroke="#2e1d0f" stroke-width="2"')
dw+=rect(0,0,W,320,'#000',0,.25)
dw+="".join(g(poly([(x,y+8),(x+8,y),(x+18,y+4),(x+16,y+16),(x+4,y+16)],'#ffc857',1,GLOW)+circ(x+8,y+6,3,'#fff',.7)) for (x,y) in [(120,100),(500,220),(440,80),(200,260),(560,140)])
dw+=g(rect(300,186,42,48,'#c23b3b',6)+circ(321,166,17,'#f0c7a0')+rect(303,170,36,14,'#8b5a2b',3)+poly([(300,166),(321,120),(342,166)],'#c23b3b')+rect(296,176,50,22,'#a0703a',6)+rect(347,190,8,46,'#8b5a2b')+rect(336,182,30,14,'#aab2bb',3)+rect(304,234,12,16,'#333')+rect(326,234,12,16,'#333'),SHADOW)
dw+=circ(210,80,24,'#ff8c42',.9,GLOW)+rect(206,100,8,40,'#5a3e2b')+circ(210,80,70,'#ff8c42',.18,SOFT)+circ(320,200,120,'#ff8c42',.12,SOFT)
games['dwarfs']=('#2a1a0e','#4a2e14','#ffc857', dw)
# ---------- Castle Wolf (woolfie) ----------
wf=""
for y in range(0,320,80):
    for x in range(0,W,80):
        wf+=rect(x,y,80,80,'#5a4632' if (x//80+y//80)%2 else '#4a3a28')+rect(x,y,80,80,'none',0,1,'stroke="#2e2316" stroke-width="2"')
wf+=rect(0,0,W,320,'#000',0,.4)+rect(0,0,W,320,'none',0,1)
wf+=circ(120,200,90,'#ffc857',.1,SOFT)
wf+=rect(260,110,120,140,'#2a1f14',4)+rect(270,120,100,120,'#1a140c',4)+g(rect(300,140,40,90,'#8b2b2b',3)+rect(304,144,32,82,'#a03535',2)+circ(332,186,5,'#ffc857',1,GLOW),SHADOW)
wf+=g(circ(120,200,16,'#e0e0e0')+rect(112,216,16,40,'#2c3e50',3)+rect(104,222,8,22,'#2c3e50',3)+rect(128,222,8,22,'#2c3e50',3)+rect(126,230,24,6,'#555',2),SHADOW)
wf+=g(circ(520,100,16,'#8b8b8b')+rect(512,116,16,40,'#3b3b3b',3)+line(528,130,560,120,'#8b8b8b',5)+rect(544,112,20,8,'#555',2),SHADOW)+poly([(520,100),(610,60),(610,140)],'#ffc857',.15)
wf+="".join(line(x,0,x,320,'#000',6,.3) for x in [200,440])+text(24,32,'KEYS 1/3','#ffc857',16)
games['woolfie']=('#1a1a1a','#3a2a1a','#c0392b', wf)
# ---------- Legal Memory ----------
lm=""
layout=[(90,50,'back'),(185,50,'scale'),(280,50,'back'),(375,50,'back'),(470,50,'scale'),(90,170,'back'),(185,170,'back'),(280,170,'gavel'),(375,170,'back'),(470,170,'back')]
for (x,y,k) in layout:
    if k=='back': lm+=card(x,y,76,100,'#2d4a7a',True)
    else:
        lm+=card(x,y,76,100,'#ffc857')
        if k=='scale': lm+=rect(x+36,y+20,4,56,'#1b2a4a')+rect(x+16,y+24,44,4,'#1b2a4a')+path(f"M {x+16} {y+26} L {x+8} {y+50} A 10 6 0 0 0 {x+28} {y+50} Z",'#1b2a4a')+path(f"M {x+60} {y+26} L {x+52} {y+50} A 10 6 0 0 0 {x+72} {y+50} Z",'#1b2a4a')+rect(x+24,y+76,28,6,'#1b2a4a',2)
        else: lm+=rect(x+14,y+22,30,20,'#1b2a4a',4,1,'transform="rotate(-35 %d %d)"'%(x+29,y+32))+line(x+36,y+40,x+60,y+70,'#1b2a4a',8)+rect(x+16,y+76,44,8,'#1b2a4a',3)
lm+=rect(90,300,456,8,'#ffc857',4,.4)+text(24,32,'PAIRS 2/5','#ffc857',16)
games['lawyer-memory']=('#132038','#2d4a7a','#ffc857', lm)
# ---------- Doctor Memory ----------
dm=""
layout=[(90,50,'cross'),(185,50,'back'),(280,50,'back'),(375,50,'cross'),(470,50,'back'),(90,170,'back'),(185,170,'back'),(280,170,'pill'),(375,170,'back'),(470,170,'back')]
for (x,y,k) in layout:
    if k=='back': dm+=card(x,y,76,100,'#2b7fa8',True)
    else:
        dm+=card(x,y,76,100,'#eaf4ff')
        if k=='cross': dm+=rect(x+30,y+22,16,56,'#e74c3c',3)+rect(x+10,y+42,56,16,'#e74c3c',3)
        else: dm+=rect(x+14,y+36,48,26,'#3ddc97',13,1,'transform="rotate(-30 %d %d)"'%(x+38,y+49))+rect(x+14,y+36,24,26,'#fff',13,1,'transform="rotate(-30 %d %d)"'%(x+38,y+49))
dm+=path("M 20 300 L 120 300 L 140 270 L 160 330 L 180 290 L 200 300 L 620 300",'none','#3ddc97',3,.9,GLOW)+text(24,32,'PAIRS 2/5','#3ddc97',16)
games['doctor-memory']=('#0b3b5a','#1b6a8a','#3ddc97', dm)
# ---------- Frogger ----------
fr="".join(rect(0,y,W,50,c) for (y,c) in [(0,'#2d6a4f'),(50,'#1b4b7a'),(100,'#1b4b7a'),(150,'#5a3e2b'),(200,'#2a2a2a'),(250,'#2a2a2a'),(300,'#2d6a4f')])
fr+="".join(rect(x,205,W,2,'#ffc857',0,.5) for x in [0])+"".join(rect(x,248,30,3,'#fff',0,.6) for x in range(0,W,60))
fr+="".join(g(rect(x,58,110,34,'#8b5a2b',8)+"".join(line(x+10+i*25,62,x+10+i*25,88,'#6b4423',2) for i in range(4)),SHADOW) for x in [40,280,520])
fr+="".join(g(ell(x+40,125,44,16,'#5a9e3a')+ell(x+40,122,40,12,'#7ac943')+ell(x+40,122,10,8,'#5a9e3a',.5),SHADOW) for x in [100,340,560])
fr+="".join(g(rect(x,206,70,36,c,8)+rect(x+12,210,46,14,'#9ad8ff',4)+circ(x+14,244,7,'#111')+circ(x+56,244,7,'#111')+circ(x+68,222,3,'#fff',1,GLOW),SHADOW) for (x,c) in [(80,'#ff3b81'),(300,'#00d1ff'),(520,'#ff3b81')])
fr+="".join(g(rect(x,256,80,36,'#ffc857',6)+rect(x+6,260,24,14,'#9ad8ff',3)+circ(x+16,294,7,'#111')+circ(x+64,294,7,'#111'),SHADOW) for x in [200,440])
fr+=g(ell(320,325,20,16,'#7ac943')+circ(311,314,5,'#fff')+circ(329,314,5,'#fff')+circ(312,314,2.5,'#000')+circ(330,314,2.5,'#000')+ell(300,330,8,5,'#7ac943')+ell(340,330,8,5,'#7ac943'),SHADOW)
games['frogger']=('#0b1a2b','#163e5a','#7ac943', fr)
# ---------- Bomberman ----------
bm=""
for y in range(0,320,40):
    for x in range(0,W,40):
        bm+=rect(x,y,40,40,'#4e9d5c' if (x//40+y//40)%2 else '#45905a')
for x in range(40,W,80):
    for y in range(40,320,80): bm+=rect(x,y,40,40,'#8d96a0')+rect(x,y,40,6,'#c9d0d6')+rect(x,y+34,40,6,'#5f666d')
for (x,y) in [(0,80),(80,160),(200,0),(280,120),(360,240),(520,80),(600,200),(120,240),(440,0),(560,280)]:
    bm+=rect(x+2,y+2,36,36,'#9a6a3c',3)+"".join(rect(x+2+ (i%2)*18,y+2+(i//2)*12,16,10,'#7d5230',1) for i in range(6))
bm+=g(circ(320,200,30,'#111')+circ(310,190,9,'#444',.8)+rect(316,160,8,14,'#8b5a2b',2)+circ(320,156,7,'#ffc857',1,GLOW)+circ(320,156,14,'#ff8c42',.4,GLOW),SHADOW)
bm+=g(circ(140,108,20,'#fff')+rect(126,126,28,36,'#00d1ff',8)+circ(133,104,3.5,'#111')+circ(147,104,3.5,'#111')+rect(130,86,20,10,'#00d1ff',4)+rect(122,140,8,18,'#ff8c42',3)+rect(150,140,8,18,'#ff8c42',3),SHADOW)
bm+="".join(g(rect(x,y,34,34,'#ff3b81',8)+rect(x+4,y+4,26,26,'#ff6aa0',6),SHADOW) for (x,y) in [(443,123),(563,43)])
games['bomberman']=('#1d6b3a','#2e8b57','#ffc857', bm)

TITLES={'chopper':'Chopper','blockworld':'Blockworld','towers':'Towers','dungeon':'Dungeon Adventure','space-invaders':'Space Invaders','lawyer-memory':'Legal Memory','woolfie':'Castle Wolf','bomberman':'Bomberman','frogger':'Frogger','tetris':'Tetris','pacman':'Pac-Man','snake':'Snake','pong':'Pong','reversi':'Reversi','minesweeper':'Minesweeper','asteroids':'Asteroids','missile-command':'Missile Command','candy':'Candy Rush','space-huggers':'Space Huggers','miniciv':'MiniCiv','doctor-memory':'Doctor Memory','dwarfs':'Dwarfs'}
assert set(TITLES)==set(games), set(TITLES)^set(games)
defs='''<defs>
<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{c1}"/><stop offset="1" stop-color="{c2}"/></linearGradient>
<linearGradient id="fade" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".85"/></linearGradient>
<radialGradient id="vig" cx=".5" cy=".45" r=".75"><stop offset=".6" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".55"/></radialGradient>
<pattern id="scan" width="4" height="4" patternUnits="userSpaceOnUse"><rect width="4" height="2" fill="#000"/></pattern>
<filter id="glow" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="3" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
<filter id="soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="18"/></filter>
<filter id="shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="6" stdDeviation="5" flood-color="#000" flood-opacity=".5"/></filter>
</defs>'''
for slug,(c1,c2,acc,body) in games.items():
    title=TITLES[slug]
    svg=f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" role="img" aria-label="{title}">
{defs.format(c1=c1,c2=c2)}
<rect width="{W}" height="{H}" fill="url(#bg)"/>
{body}
{scanlines()}
{vignette()}
<rect x="0" y="{H-120}" width="{W}" height="120" fill="url(#fade)"/>
<rect x="24" y="{H-54}" width="8" height="30" fill="{acc}" rx="2" filter="url(#glow)"/>
<text x="44" y="{H-30}" font-family="ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial" font-size="30" font-weight="800" fill="#fff" letter-spacing="1">{title}</text>
</svg>'''
    open(f'public_html/images/games/{slug}.svg','w').write(svg)
print(len(games),"tiles")
