from pathlib import Path
from base64 import b64encode
from html import escape
import shutil

from PIL import Image, ImageOps
import qrcode
import cairosvg

ROOT = Path(__file__).resolve().parent
OUT = ROOT / 'banner-output'
OUT.mkdir(exist_ok=True)
W, H = 1728, 4320  # 72 SVG units per inch: 24 x 60 inches

NAVY, BLUE, CYAN, INK, MUTED, PALE, WHITE = '#082A55', '#1267C5', '#10B7D9', '#12233D', '#53657B', '#EEF8FC', '#FFFFFF'

def b64(path):
    return b64encode(Path(path).read_bytes()).decode()

def img_tag(path, x, y, w, h, radius=28):
    data = b64(path)
    ext = Path(path).suffix.lower().replace('.', '')
    if ext == 'jpg': ext = 'jpeg'
    clip = f'clip{int(x)}{int(y)}'
    return f'''<defs><clipPath id="{clip}"><rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{radius}"/></clipPath></defs>
    <rect x="{x-8}" y="{y-8}" width="{w+16}" height="{h+16}" rx="{radius+8}" fill="#D3E5F5"/>
    <image x="{x}" y="{y}" width="{w}" height="{h}" preserveAspectRatio="xMidYMid slice" clip-path="url(#{clip})" href="data:image/{ext};base64,{data}"/>'''

def text(x, y, value, size, fill=INK, weight=400, anchor='start', family='Arial'):
    return f'<text x="{x}" y="{y}" font-family="{family}" font-size="{size}" font-weight="{weight}" fill="{fill}" text-anchor="{anchor}">{escape(value)}</text>'

def multiline(x, y, lines, size, leading=1.34, fill=INK, weight=400, anchor='start'):
    return ''.join(text(x, y+i*size*leading, line, size, fill, weight, anchor) for i,line in enumerate(lines))

def card(x, y, icon, title, lines, color):
    return f'''<rect x="{x}" y="{y}" width="720" height="254" rx="30" fill="#FFFFFF" stroke="#D9E8F4" stroke-width="3"/>
    <circle cx="{x+80}" cy="{y+77}" r="42" fill="{color}"/>{text(x+80,y+91,icon,42,WHITE,700,'middle','Arial')}
    {text(x+145,y+76,title,31,NAVY,700)}{multiline(x+145,y+120,lines,23,1.35,MUTED)}'''

def team_placeholder(x, name, role):
    return f'''<circle cx="{x}" cy="3922" r="105" fill="#E5F3FA" stroke="#9FC9E4" stroke-width="4"/>
    <circle cx="{x}" cy="3888" r="32" fill="#78B6DC"/><path d="M {x-62} 3988 Q {x} 3908 {x+62} 3988" fill="#78B6DC"/>
    {text(x,4070,name,23,NAVY,700,'middle')}{text(x,4111,role,18,MUTED,400,'middle')}'''

def main():
    qr_path = OUT / 'live-demo-qr.png'
    qr = qrcode.QRCode(version=None, error_correction=qrcode.constants.ERROR_CORRECT_H, box_size=16, border=4)
    qr.add_data('https://ai-smart-campus-system-ce9i.onrender.com'); qr.make(fit=True)
    qr.make_image(fill_color='#082A55', back_color='white').save(qr_path)

    # Explicit source copies make the editable SVG self-contained and the screenshot provenance clear.
    sources = [
        ROOT/'testing-evidence/13-student-dashboard.png',
        ROOT/'testing-evidence/19-ai-bangla-assistant.png',
        ROOT/'testing-evidence/18-student-risk-monitoring.png',
    ]
    names = ['student-dashboard.png','ai-assistant.png','risk-monitoring.png']
    for src, name in zip(sources, names):
        shutil.copy2(src, OUT/name)

    svg = [f'''<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24in" height="60in" viewBox="0 0 {W} {H}">
    <defs>
      <linearGradient id="hero" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#062657"/><stop offset=".62" stop-color="#0D5EAD"/><stop offset="1" stop-color="#0BAFCB"/></linearGradient>
      <linearGradient id="band" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#E6F7FC"/><stop offset="1" stop-color="#F7FBFF"/></linearGradient>
      <filter id="shadow"><feDropShadow dx="0" dy="9" stdDeviation="12" flood-color="#09366A" flood-opacity=".13"/></filter>
    </defs>
    <rect width="{W}" height="{H}" fill="#F9FCFF"/>
    <rect x="0" y="0" width="{W}" height="740" fill="url(#hero)"/>
    <circle cx="1500" cy="130" r="260" fill="#5ACFE4" opacity=".16"/><circle cx="1350" cy="530" r="330" fill="#7BD4EC" opacity=".12"/>
    <path d="M0 655 Q430 790 920 690 T1728 670 V740 H0Z" fill="#F9FCFF"/>
    <g opacity=".4" stroke="#B6EEFA" fill="none"><path d="M1160 210h440M1250 120v315M1350 70v420M1450 100v340M1550 160v200"/><circle cx="1300" cy="170" r="9" fill="#B6EEFA"/><circle cx="1490" cy="300" r="9" fill="#B6EEFA"/></g>
    {text(108,130,'NORTHERN UNIVERSITY OF BUSINESS AND TECHNOLOGY, KHULNA',24,'#C7F4FC',700)}
    {text(108,250,'AI SMART',88,WHITE,800)}{text(108,355,'CAMPUS SYSTEM',88,WHITE,800)}
    {text(111,430,'Connected campus operations. Intelligent student success.',31,'#D5F7FC',400)}
    <rect x="110" y="500" width="455" height="60" rx="30" fill="#FFFFFF" opacity=".16"/>{text(337,540,'CSE 4204 • FINAL PROJECT',23,WHITE,700,'middle')}
    <g transform="translate(1320 310)"><circle r="135" fill="#FFFFFF" opacity=".12"/><path d="M-92 4 L0-43 92 4 0 51Z" fill="#D6F7FC"/><path d="M-55 24V94H55V24" fill="none" stroke="#D6F7FC" stroke-width="13"/><path d="M0 51V118" stroke="#D6F7FC" stroke-width="13"/><circle cx="0" cy="-43" r="16" fill="#10B7D9"/></g>

    <rect x="96" y="772" width="1536" height="280" rx="30" fill="url(#band)"/><rect x="96" y="772" width="13" height="280" rx="7" fill="#10B7D9"/>
    {text(145,850,'PROJECT OVERVIEW',27,BLUE,700)}
    {multiline(145,908,['A role-aware academic management platform for NUBTK that connects students, faculty,','and administrators with trusted academic records, campus services, and AI-assisted','decision support—improving visibility, timely action, and everyday campus coordination.'],29,1.32,INK)}

    {text(96,1155,'WHAT THE SYSTEM DELIVERS',37,NAVY,800)}{text(96,1204,'Verified features drawn from the implemented React + Laravel application',22,MUTED)}
    {card(96,1250,'◉','Role-Based Access',['Approval-aware authentication and','scoped student, faculty & admin views.'],BLUE)}
    {card(912,1250,'▦','Academic Workspace',['Courses, attendance, grades,','routines, records and transcript export.'],CYAN)}
    {card(96,1530,'✦','AI Campus Assistant',['Context-aware English, Bangla &','mixed-language academic guidance.'],'#7457C7')}
    {card(912,1530,'↗','Smart Recommendations',['Gemini-assisted course ranking','with deterministic fallback.'],'#16A36A')}
    {card(96,1810,'!','Risk Monitoring',['Authorized faculty/admin analysis','with supportive action insights.'],'#E37B29')}
    {card(912,1810,'✓','Campus Services',['Notices, tasks, routines, exams,','events, fees and support tools.'],'#D45485')}

    <rect x="0" y="2110" width="1728" height="410" fill="#082A55"/>
    {text(96,2190,'SIMPLIFIED SYSTEM WORKFLOW',33,WHITE,800)}{text(96,2235,'Secure, backend-controlled paths turn trusted campus data into useful actions.',21,'#CBEAF4')}
    <g font-family="Arial" font-weight="700" text-anchor="middle">
      <rect x="105" y="2310" width="235" height="105" rx="23" fill="#FFFFFF"/>{text(222,2374,'Users',26,NAVY,700,'middle')}
      <path d="M360 2362 H470" stroke="#4BCBE0" stroke-width="8"/><path d="M455 2347l25 15-25 15" fill="none" stroke="#4BCBE0" stroke-width="8"/>
      <rect x="495" y="2310" width="265" height="105" rx="23" fill="#FFFFFF"/>{text(628,2374,'Secure Access',26,NAVY,700,'middle')}
      <path d="M780 2362 H890" stroke="#4BCBE0" stroke-width="8"/><path d="M875 2347l25 15-25 15" fill="none" stroke="#4BCBE0" stroke-width="8"/>
      <rect x="915" y="2310" width="305" height="105" rx="23" fill="#FFFFFF"/>{text(1068,2374,'Campus Context',26,NAVY,700,'middle')}
      <path d="M1240 2362 H1350" stroke="#4BCBE0" stroke-width="8"/><path d="M1335 2347l25 15-25 15" fill="none" stroke="#4BCBE0" stroke-width="8"/>
      <rect x="1370" y="2310" width="250" height="105" rx="23" fill="#10B7D9"/>{text(1495,2374,'Results',26,WHITE,700,'middle')}
    </g>

    {text(96,2620,'REAL SOFTWARE • VERIFIED UI EVIDENCE',37,NAVY,800)}{text(96,2670,'Screens captured from the project’s documented test evidence',22,MUTED)}
    {img_tag(OUT/'student-dashboard.png',96,2720,960,420)}{img_tag(OUT/'ai-assistant.png',1095,2720,537,420)}
    {text(96,3188,'Student dashboard',23,NAVY,700)}{text(1095,3188,'AI assistant',23,NAVY,700)}
    {img_tag(OUT/'risk-monitoring.png',96,3235,1536,365)}{text(96,3645,'Authorized academic risk monitoring',23,NAVY,700)}

    <rect x="0" y="3700" width="1728" height="620" fill="#EAF7FC"/>
    {text(96,3775,'BUILT WITH',28,BLUE,800)}
    {text(96,3835,'React 19  •  Vite  •  Laravel 12  •  PHP 8.2+  •  MySQL  •  Sanctum',25,NAVY,700)}
    {text(96,3885,'Google Gemini API  •  OpenAI Responses API  •  Bootstrap  •  Material UI',23,MUTED,400)}
    <rect x="1175" y="3748" width="380" height="420" rx="30" fill="white" filter="url(#shadow)"/>
    <image x="1225" y="3788" width="280" height="280" href="data:image/png;base64,{b64(qr_path)}"/>
    {text(1365,4115,'SCAN TO EXPLORE',20,NAVY,800,'middle')}{text(1365,4148,'LIVE DEMO',20,NAVY,800,'middle')}
    {text(96,3940,'PROJECT TEAM',28,BLUE,800)}
    {team_placeholder(210,'Md. Nazmus Shakib','Team Leader / Database')}
    {team_placeholder(555,'Samira Akter Mitu','AI')}
    {team_placeholder(900,'Tanvin Sadik Dhrubo','Frontend')}
    {team_placeholder(210,'Khan Waziur Rahman','Backend')}
    <rect x="96" y="4182" width="960" height="2" fill="#BBD8E8"/>{text(96,4230,'Portrait placeholders — original team photos were not available in the project folder.',18,MUTED)}
    </svg>''']
    # Correct the final team-member layout that is intentionally compact in the source above.
    content = ''.join(svg).replace(team_placeholder(210,'Khan Waziur Rahman','Backend'), team_placeholder(900,'Khan Waziur Rahman','Backend'))
    svg_path = OUT/'AI_Smart_Campus_XBanner_Editable.svg'
    svg_path.write_text(content, encoding='utf-8')
    cairosvg.svg2pdf(bytestring=content.encode(), write_to=str(OUT/'AI_Smart_Campus_XBanner_PrintReady.pdf'), output_width=1728, output_height=4320)
    cairosvg.svg2png(bytestring=content.encode(), write_to=str(OUT/'AI_Smart_Campus_XBanner_Preview.png'), output_width=1728, output_height=4320)

    report = '''# AI Smart Campus System — X-Banner Design Report

## Verified project content

The banner is based on the repository README and implemented pages/controllers: role-aware authentication, student/faculty/admin workspaces, academic records and exports, campus services, notices/tasks, Gemini campus assistant, Gemini-assisted course recommendation with fallback, and OpenAI-assisted authorized risk analysis.

## Technology stack

React 19, Vite, Bootstrap, Material UI, Axios; Laravel 12, PHP 8.2+, Laravel Sanctum; MySQL; Google Gemini Generate Content API; OpenAI Responses API.

## Evidence used

`testing-evidence/13-student-dashboard.png`, `testing-evidence/19-ai-bangla-assistant.png`, and `testing-evidence/18-student-risk-monitoring.png` were copied into this output directory and embedded in the editable SVG/PDF.

## Team assets

Names and roles were verified from `README.md`. Original team photographs were not found in the project folder, so the exported banner intentionally uses labeled portrait placeholders; no synthetic faces were generated.

## QR verification

The QR code encodes exactly: `https://ai-smart-campus-system-ce9i.onrender.com`. It was generated with high error correction (H) and a four-module quiet zone. The PNG is saved as `live-demo-qr.png`.

## Export and print notes

The PDF and SVG are 24 × 60 inches (1728 × 4320 SVG points at 72 units/inch), portrait, with a 1:2.5 aspect ratio. The composition keeps primary content inside approximately 1.33-inch side margins. The artwork is RGB because a reliable ICC CMYK conversion profile was not available; ask the print shop to convert/proof in its press profile. No bleed was added because X-banner shops commonly trim/mount differently—confirm their bleed specification before production.
'''
    (OUT/'Banner_Design_Report.md').write_text(report, encoding='utf-8')

if __name__ == '__main__': main()
