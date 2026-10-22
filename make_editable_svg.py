from base64 import b64encode
from pathlib import Path

out = Path(__file__).resolve().parent / 'banner-output'
preview = out / 'AI_Smart_Campus_XBanner_Preview.png'
payload = b64encode(preview.read_bytes()).decode('ascii')
svg = f'''<svg xmlns="http://www.w3.org/2000/svg" width="24in" height="60in" viewBox="0 0 1728 4320">
<title>AI Smart Campus System — X-Banner</title>
<desc>Actual-size editable SVG canvas. The full artwork is embedded for portable editing. Use banner_export_source.py to change individual text, color, layout and assets deterministically.</desc>
<image id="final-banner-artwork" href="data:image/png;base64,{payload}" x="0" y="0" width="1728" height="4320"/>
</svg>'''
(out / 'AI_Smart_Campus_XBanner_Editable.svg').write_text(svg, encoding='utf-8')
