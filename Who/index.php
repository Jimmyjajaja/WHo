<?php
// index.php — Who Is It? (two players, mobile-friendly)
// Frontend UI (HTML/CSS/JS). Realtime via simple polling to api.php
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Who Is It? — PHP (2P)</title>
<style>
:root { --bg:#0f1220; --card:#151934; --ink:#e9ecff; --muted:#aab2ff; --accent:#7c8cff; --accent-2:#3ee0a1; }
*{box-sizing:border-box}
body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,"TH Sarabun New",Arial,sans-serif;background:radial-gradient(1200px 800px at 80% -10%,#1a1f45 0%,var(--bg) 60%);color:var(--ink)}
header{padding:16px;display:flex;gap:12px;align-items:center;justify-content:space-between}
h1{margin:0;font-size:clamp(18px,4vw,28px)}
.wrap{max-width:1100px;margin:0 auto;padding:12px}
.panel{display:grid;grid-template-columns:1fr;gap:12px;background:var(--card);padding:12px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.25)}
@media(min-width:860px){.panel{grid-template-columns:1.2fr 1fr}}
.pill{display:inline-flex;align-items:center;gap:8px;background:#1b2050;padding:6px 10px;border-radius:999px;font-size:13px;color:var(--muted)}
.controls{display:grid;gap:10px;align-content:start}
.controls .row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.controls button,.controls select{width:100%;padding:10px 12px;border-radius:12px;border:1px solid transparent;background:#1b2050;color:var(--ink)}
.controls button{cursor:pointer;font-weight:700}
.controls button.primary{background:linear-gradient(90deg,#5c6cff,#3ee0a1);color:#061018}
.controls button.ghost{background:transparent;border-color:#2b3270;color:var(--muted)}
.controls .note{color:var(--muted);font-size:13px}
.board{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
@media(min-width:520px){.board{grid-template-columns:repeat(3,1fr)}}
@media(min-width:860px){.board{grid-template-columns:repeat(4,1fr)}}
.card{position:relative;background:linear-gradient(180deg,#1a1f45,#121636);border:1px solid #2b3270;border-radius:16px;overflow:hidden;display:flex;flex-direction:column}
.card img{width:100%;height:170px;object-fit:contain;background:#0d112b}
.card .body{padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px}
.card .name{font-weight:700}
.card .attrs{font-size:12px;color:var(--muted)}
.card.eliminated{filter:grayscale(1) brightness(.6);opacity:.6}
.chip{font-size:11px;background:#232a6b;color:#b7c1ff;padding:2px 6px;border-radius:999px}
footer{text-align:center;color:#8993ff;font-size:12px;padding:20px}
a{color:var(--accent-2);text-decoration:none}
.toast{position:fixed;right:16px;bottom:16px;background:#11152e;border:1px solid #2b3270;padding:10px 14px;border-radius:12px;color:var(--ink);opacity:0;transform:translateY(10px);transition:all .25s ease}
.toast.show{opacity:1;transform:translateY(0)}
/* lobby modal */
.modal{position:fixed;inset:0;background:rgba(8,10,24,.75);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px}
.modal .box{width:min(520px,100%);background:#0f1330;border:1px solid #2b3270;border-radius:16px;padding:16px;display:grid;gap:10px}
.modal input{width:100%;padding:10px 12px;border-radius:12px;border:1px solid #2b3270;background:#12163c;color:var(--ink)}
.log{max-height:220px;overflow:auto;background:#0f1330;border:1px solid #2b3270;border-radius:12px;padding:8px;font-size:12px;color:#b7c1ff}
.log .e{margin:4px 0}
</style>
</head>
<body>
<header class="wrap">
<h1>Who Is It? <span class="pill" id="status-pill">ยังไม่เชื่อมต่อ</span></h1>
<div style="display:flex; gap:8px">
<button class="ghost" id="btn-help">วิธีเล่น</button>
<button class="primary" id="btn-new">เริ่มใหม่</button>
</div>
</header>


<main class="wrap panel">
<section>
<div class="board" id="board"></div>
</section>
<aside class="controls">
<div class="row">
<select id="attr">
<option value="">— เลือกคุณสมบัติ —</option>
<option value="gender">เพศ</option>
<option value="hair">สีผม</option>
<option value="glasses">ใส่แว่น</option>
<option value="facial_hair">หนวดเครา</option>
<option value="hat">สวมหมวก</option>
<option value="earrings">ต่างหู</option>
<option value="eye_color">สีตา</option>
<option value="skin_tone">สีผิว</option>
</select>
<select id="value"><option value="">— เลือกค่า —</option></select>
<button class="primary" id="btn-ask">ถาม</button>
</div>
</html>