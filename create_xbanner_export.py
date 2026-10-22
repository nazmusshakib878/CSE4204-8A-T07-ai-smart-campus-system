from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, ImageOps
from reportlab.pdfgen import canvas
from reportlab.lib.utils import ImageReader
from reportlab.lib.colors import HexColor
import qrcode

ROOT=Path(__file__).resolve().parent; OUT=ROOT/'banner-output'; OUT.mkdir(exist_ok=True)
W,H=1728,4320
navy='#082A55'; blue='#1267C5'; cyan='#10B7D9'; ink='#12233D'; muted='#53657B'
def font(n,b=False):
    p='C:/Windows/Fonts/arialbd.ttf' if b else 'C:/Windows/Fonts/arial.ttf'
    return ImageFont.truetype(p,n)
def block(d,xy,text,size,fill=ink,bold=False,spacing=10): d.multiline_text(xy,text,font=font(size,bold),fill=fill,spacing=spacing)
def rr(d,box,r,fill,outline=None,w=1): d.rounded_rectangle(box,r,fill,outline,width=w)
def fitim(path,w,h):
    im=Image.open(path).convert('RGB'); return ImageOps.fit(im,(w,h),Image.Resampling.LANCZOS)
def main():
    qr=OUT/'live-demo-qr.png'; q=qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_H,box_size=16,border=4); q.add_data('https://ai-smart-campus-system-ce9i.onrender.com');q.make(fit=True);q.make_image(fill_color=navy,back_color='white').save(qr)
    im=Image.new('RGB',(W,H),'#F9FCFF');d=ImageDraw.Draw(im)
    d.rectangle((0,0,W,740),fill=navy); d.ellipse((1200,-120,1800,480),fill='#0C5FAE');d.ellipse((1050,210,1720,800),fill='#0CAAC5')
    block(d,(108,130),'NORTHERN UNIVERSITY OF BUSINESS AND TECHNOLOGY, KHULNA',24,'#C7F4FC',True);block(d,(108,245),'AI SMART\nCAMPUS SYSTEM',88,'white',True,4);block(d,(111,455),'Connected campus operations. Intelligent student success.',31,'#D5F7FC')
    rr(d,(110,520,565,580),30,'#367FBF');block(d,(163,538),'CSE 4204  •  FINAL PROJECT',23,'white',True)
    rr(d,(96,772,1632,1052),30,'#EAF7FC');d.rectangle((96,772,109,1052),fill=cyan);block(d,(145,850),'PROJECT OVERVIEW',27,blue,True);block(d,(145,908),'A role-aware academic management platform for NUBTK that connects students, faculty,\nand administrators with trusted academic records, campus services, and AI-assisted\ndecision support—improving visibility, timely action, and everyday campus coordination.',29)
    block(d,(96,1155),'WHAT THE SYSTEM DELIVERS',37,navy,True);block(d,(96,1204),'Verified features drawn from the implemented React + Laravel application',22,muted)
    features=[('◉','Role-Based Access','Approval-aware authentication and\nscoped student, faculty & admin views.',blue),('▦','Academic Workspace','Courses, attendance, grades,\nroutines, records and transcript export.',cyan),('✦','AI Campus Assistant','Context-aware English, Bangla &\nmixed-language academic guidance.','#7457C7'),('↗','Smart Recommendations','Gemini-assisted course ranking\nwith deterministic fallback.','#16A36A'),('!','Risk Monitoring','Authorized faculty/admin analysis\nwith supportive action insights.','#E37B29'),('✓','Campus Services','Notices, tasks, routines, exams,\nevents, fees and support tools.','#D45485')]
    for i,(ico,title,desc,col) in enumerate(features):
        x=96+(816 if i%2 else 0);y=1250+(280*(i//2));rr(d,(x,y,x+720,y+254),30,'white','#D9E8F4',3);d.ellipse((x+38,y+35,x+122,y+119),fill=col);block(d,(x+68,y+54),ico,38,'white',True);block(d,(x+145,y+58),title,31,navy,True);block(d,(x+145,y+112),desc,23,muted)
    d.rectangle((0,2110,W,2520),fill=navy);block(d,(96,2190),'SIMPLIFIED SYSTEM WORKFLOW',33,'white',True);block(d,(96,2235),'Secure, backend-controlled paths turn trusted campus data into useful actions.',21,'#CBEAF4')
    nodes=[('Users',105,235),('Secure Access',495,265),('Campus Context',915,305),('Results',1370,250)]
    for label,x,w in nodes: rr(d,(x,2310,x+w,2415),23,cyan if label=='Results' else 'white'); tw=d.textbbox((0,0),label,font=font(26,True))[2];block(d,(x+(w-tw)//2,2345),label,26,'white' if label=='Results' else navy,True)
    for x in [360,780,1240]: d.line((x,2362,x+110,2362),fill='#4BCBE0',width=8);d.polygon([(x+110,2362),(x+84,2347),(x+84,2377)],fill='#4BCBE0')
    block(d,(96,2620),'REAL SOFTWARE • VERIFIED UI EVIDENCE',37,navy,True);block(d,(96,2670),'Screens captured from the project’s documented test evidence',22,muted)
    shots=[(ROOT/'testing-evidence/13-student-dashboard.png',(96,2720,960,420),'Student dashboard'),(ROOT/'testing-evidence/19-ai-bangla-assistant.png',(1095,2720,537,420),'AI assistant'),(ROOT/'testing-evidence/18-student-risk-monitoring.png',(96,3235,1536,365),'Authorized academic risk monitoring')]
    for src,(x,y,w,h),label in shots: im.paste(fitim(src,w,h),(x,y));d.rounded_rectangle((x,y,x+w,y+h),28,outline='#A9CBE2',width=5);block(d,(x,y+h+46),label,23,navy,True)
    d.rectangle((0,3700,W,H),fill='#EAF7FC');block(d,(96,3775),'BUILT WITH',28,blue,True);block(d,(96,3835),'React 19  •  Vite  •  Laravel 12  •  PHP 8.2+  •  MySQL  •  Sanctum',25,navy,True);block(d,(96,3885),'Google Gemini API  •  OpenAI Responses API  •  Bootstrap  •  Material UI',23,muted)
    rr(d,(1175,3748,1555,4168),30,'white'); qim=fitim(qr,280,280);im.paste(qim,(1225,3788));block(d,(1236,4115),'SCAN TO EXPLORE\nLIVE DEMO',20,navy,True,5)
    block(d,(96,3940),'PROJECT TEAM',28,blue,True)
    for x,name,role in [(205,'Md. Nazmus Shakib','Team Leader / Database'),(460,'Samira Akter Mitu','AI'),(715,'Tanvin Sadik Dhrubo','Frontend'),(970,'Khan Waziur Rahman','Backend')]:
        d.ellipse((x-82,3858,x+82,4022),fill='#E5F3FA',outline='#9FC9E4',width=4);d.ellipse((x-25,3887,x+25,3937),fill='#78B6DC');d.pieslice((x-50,3925,x+50,4010),180,360,fill='#78B6DC');tw=d.textbbox((0,0),name,font=font(18,True))[2];block(d,(x-tw//2,4050),name,18,navy,True);tw=d.textbbox((0,0),role,font=font(15))[2];block(d,(x-tw//2,4082),role,15,muted)
    d.line((96,4182,1056,4182),fill='#BBD8E8',width=2);block(d,(96,4230),'Portrait placeholders — original team photos were not available in the project folder.',18,muted)
    png=OUT/'AI_Smart_Campus_XBanner_Preview.png';im.save(png,dpi=(72,72),compress_level=6)
    # PDF has an exact physical page size, using the validated preview at full page extent.
    c=canvas.Canvas(str(OUT/'AI_Smart_Campus_XBanner_PrintReady.pdf'),pagesize=(24*72,60*72));c.drawImage(ImageReader(im),0,0,width=24*72,height=60*72,mask='auto');c.showPage();c.save()
if __name__=='__main__':main()
