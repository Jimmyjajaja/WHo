<?php
// ttt.php — Tic-Tac-Toe 2 ผู้เล่น (PHP ไฟล์เดียว, โทนมืดการ์ดมน)
//
// Storage: ไฟล์ JSON ต่อห้องในโฟลเดอร์ /data
// API: ?action=create|join|state|move|reset
session_start();

$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) mkdir($DATA_DIR, 0777, true);

function clean($s){ return preg_replace('/[^A-Za-z0-9_-]/','',$s ?? ''); }
function room_path($room){ global $DATA_DIR; return $DATA_DIR.'/ttt_'.clean($room).'.json'; }
function load_room($room){
  $p = room_path($room);
  if (!file_exists($p)) return null;
  return json_decode(file_get_contents($p), true);
}
function save_room($room, $data){
  $p = room_path($room);
  file_put_contents($p, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? null;
if ($action) {
  header('Content-Type: application/json; charset=utf-8');
  $raw = file_get_contents('php://input');
  $body = json_decode($raw ?: "{}", true) ?: [];
  $room = $body['room'] ?? $_GET['room'] ?? null;

  if ($action === 'create') {
    $room = $room ?: strtoupper(substr(bin2hex(random_bytes(3)),0,6));
    $data = [
      'room' => $room,
      // players: [{id, name, mark:'X'|'O'}]
      'players' => [],
      // board: array(9) => null|'X'|'O'
      'board' => array_fill(0,9,null),
      'turn' => 'X',            // ใครเดินต่อไป
      'winner' => null,         // 'X'|'O'|'draw'|null
      'updated_at' => time()
    ];
    save_room($room, $data);
    echo json_encode(['ok'=>true,'room'=>$room]); exit;
  }

  if (!$room) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'missing room']); exit; }
  $data = load_room($room);
  if (!$data) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'room not found']); exit; }

  $me = session_id();

  if ($action === 'join') {
    $name = trim($body['name'] ?? '');
    if ($name==='') $name = 'Player'.rand(100,999);
    $exists = false; foreach($data['players'] as $p){ if($p['id']===$me){ $exists=true; break; } }
    if (!$exists && count($data['players'])<2) {
      $mark = count($data['players'])===0 ? 'X' : 'O';
      $data['players'][] = ['id'=>$me,'name'=>$name,'mark'=>$mark];
      $data['updated_at'] = time();
      save_room($room,$data);
    }
    echo json_encode(['ok'=>true,'room'=>$room,'you'=>$me,'data'=>$data]); exit;
  }

  if ($action === 'state') {
    echo json_encode(['ok'=>true,'you'=>$me,'data'=>$data]); exit;
  }

  if ($action === 'move') {
    $idx = intval($body['index'] ?? -1);
    if ($idx<0 || $idx>8) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad index']); exit; }

    // ตรวจว่าเป็นผู้เล่น และยังไม่จบเกม
    $myMark = null;
    foreach($data['players'] as $p){ if($p['id']===$me){ $myMark=$p['mark']; break; } }
    if (!$myMark) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'not a player']); exit; }
    if ($data['winner']) { echo json_encode(['ok'=>true,'data'=>$data]); exit; }

    // เดินได้เฉพาะตนเอง และช่องว่าง
    if ($data['turn'] !== $myMark) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'not your turn']); exit; }
    if ($data['board'][$idx] !== null) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'occupied']); exit; }

    $data['board'][$idx] = $myMark;
    // ตรวจผล
    $winsets = [
      [0,1,2],[3,4,5],[6,7,8],
      [0,3,6],[1,4,7],[2,5,8],
      [0,4,8],[2,4,6]
    ];
    $winner = null;
    foreach($winsets as $w){
      $a=$data['board'][$w[0]]; $b=$data['board'][$w[1]]; $c=$data['board'][$w[2]];
      if ($a && $a===$b && $b===$c) { $winner = $a; break; }
    }
    if ($winner) {
      $data['winner'] = $winner;
    } else if (!in_array(null, $data['board'], true)) {
      $data['winner'] = 'draw';
    } else {
      $data['turn'] = ($data['turn']==='X' ? 'O' : 'X');
    }

    $data['updated_at'] = time();
    save_room($room,$data);
    echo json_encode(['ok'=>true,'data'=>$data]); exit;
  }

  if ($action === 'reset') {
    $data['board'] = array_fill(0,9,null);
    $data['turn'] = 'X';
    $data['winner'] = null;
    $data['updated_at'] = time();
    save_room($room,$data);
    echo json_encode(['ok'=>true,'data'=>$data]); exit;
  }

  http_response_code(404); echo json_encode(['ok'=>false,'error'=>'unknown action']); exit;
}

// ======= UI =======
?>
<!doctype html>
<html lang="th">
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>OX 2-Player (PHP)</title>
<style>
  :root{
    --bg: #0b1220; --panel:#0f172a; --card:#0b1220; --border:#1f2937; --muted:#94a3b8;
    --accent:#22d3ee; --accent2:#38bdf8; --win:#10b981; --lose:#ef4444;
    --shadow: 0 10px 30px rgba(0,0,0,.35);
  }
  *{box-sizing:border-box}
  body{margin:0; font-family: ui-sans-serif,system-ui,"Noto Sans Thai",sans-serif; background:linear-gradient(180deg,#071226,#0f172a); color:#e5e7eb;}
  header{padding:18px 14px; border-bottom:1px solid var(--border); display:flex; gap:10px; align-items:center; justify-content:space-between;}
  header h1{font-size:18px; margin:0; letter-spacing:.3px}
  main{max-width:900px; margin:0 auto; padding:16px;}
  .card{background:var(--panel); border:1px solid var(--border); border-radius:16px; padding:14px; box-shadow:var(--shadow); margin-bottom:14px;}
  .row{display:flex; gap:8px; flex-wrap:wrap; align-items:center}
  input,button{padding:10px 12px; border-radius:12px; border:1px solid #334155; background: #0b1220; color:#e5e7eb;}
  button.primary{background: var(--accent); color:#082f49; font-weight:700; border-color:#0891b2}
  .pill{display:inline-block; padding:4px 10px; border:1px solid #334155; border-radius:999px; font-size:12px; color:#cbd5e1}
  .grid{display:grid; gap:12px; grid-template-columns: repeat(3,minmax(90px,1fr));}
  .tile{aspect-ratio:1/1; border-radius:20px; border:1px solid var(--border); background: radial-gradient(120% 120% at 0% 0%, #0b1220 0%, #0f172a 70%); display:grid; place-items:center; font-size:48px; font-weight:900; cursor:pointer; transition:.15s transform,.2s box-shadow,.2s filter;}
  .tile:hover{transform: translateY(-2px);}
  .tile.disabled{cursor:not-allowed; opacity:.5; filter:grayscale(.1)}
  .tile.win{outline:2px solid var(--win); box-shadow:0 0 0 4px rgba(16,185,129,.2) inset;}
  .sub{color:var(--muted); font-size:13px}
  .status{margin-top:8px; color:var(--muted)}
  .controls button{min-width:120px}
  @media (max-width: 520px){ .grid{grid-template-columns: repeat(3, 1fr);} .tile{font-size:40px; border-radius:16px} }
</style>

<body>
<header>
  <h1>OX 2-Player — PHP</h1>
  <div class="row">
    <button id="btnReset">รีเซ็ตกระดาน</button>
    <a id="btnHelp" href="#" class="sub">วิธีเล่น</a>
  </div>
</header>

<main>
  <div class="card">
    <div class="row">
      <input id="name" placeholder="ชื่อคุณ"/>
      <input id="room" placeholder="รหัสห้อง (เช่น ABC123)"/>
      <button id="btnCreate">สร้างห้อง</button>
      <button class="primary" id="btnJoin">เข้าห้อง</button>
    </div>
    <div class="sub" id="info" style="margin-top:6px">เริ่มจากสร้างห้องหรือเข้าห้อง แล้วชวนเพื่อนมาเล่น</div>
  </div>

  <div class="card">
    <div class="row">
      ห้อง: <span class="pill" id="roomLabel">-</span>
      ผู้เล่น: <span class="pill" id="playersLabel">-</span>
      ตาเดิน: <span class="pill" id="turnLabel">-</span>
    </div>
    <div class="status" id="status"></div>
  </div>

  <div class="card">
    <div class="grid" id="grid"></div>
  </div>

  <div class="card">
    <div id="result" class="sub">ผลลัพธ์จะแสดงที่นี่</div>
  </div>
</main>

<script>
  const $ = s=>document.querySelector(s);
  let room=null, me=null, poller=null, lastBoard=null;

  async function api(action,payload={}){
    const res = await fetch(`?action=${action}`,{
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({...payload, room})
    });
    return res.json();
  }

  async function createRoom(){
    const r = await fetch(`?action=create`,{method:'POST'}).then(r=>r.json());
    if(r.ok){ room=r.room; $('#room').value=room; $('#info').textContent='สร้างห้องแล้ว: '+room; refresh(); }
  }
  async function joinRoom(){
    const name = $('#name').value || 'Player';
    const r = await api('join',{name});
    if(r.ok){ me=r.you; room=r.room; $('#roomLabel').textContent=room; startPolling(); }
    else alert(r.error||'join failed');
  }

  function renderBoard(board, winner, turn){
    const g = $('#grid'); g.innerHTML='';
    const winLines = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
    let winSet = [];
    if(winner==='X'||winner==='O'){ // หาเส้นชนะเพื่อไฮไลต์
      for(const w of winLines){
        const [a,b,c]=w;
        if(board[a]&&board[a]===board[b]&&board[b]===board[c]){ winSet=w; break; }
      }
    }
    board.forEach((v,i)=>{
      const btn = document.createElement('button');
      btn.className='tile';
      if(v) { btn.textContent=v; btn.classList.add('disabled'); }
      if(winSet.includes(i)) btn.classList.add('win');
      btn.addEventListener('click', ()=>move(i));
      if(winner || v) btn.disabled = true; // ถ้าจบเกมหรือช่องถูกจองแล้ว ปิดคลิก
      g.appendChild(btn);
    });
    lastBoard = board.slice();
    $('#turnLabel').textContent = winner ? '-' : (turn || '-');
  }

  async function move(index){
    const r = await api('move',{index});
    if(!r.ok){ alert(r.error||'ผิดพลาด'); return; }
    updateState(r.data);
  }

  async function resetBoard(){
    const r = await api('reset');
    if(!r.ok){ alert(r.error||'ผิดพลาด'); return; }
    updateState(r.data);
  }

  async function refresh(){
    if(!room) return;
    const r = await api('state');
    if(r.ok) updateState(r.data);
  }

  function updateState(d){
    const names = {}; (d.players||[]).forEach(p=>names[p.id]=p.name || p.id);
    $('#playersLabel').textContent = (d.players||[]).map(p=> `${names[p.id]}(${p.mark})`).join(' , ') || '-';
    $('#status').textContent = d.winner
      ? (d.winner==='draw' ? 'ผล: เสมอ 🤝' : `ผู้ชนะ: ${d.winner==='X'?'X':'O'} 🎉`)
      : (d.turn ? `ถึงตา: ${d.turn}` : 'รอผู้เล่นครบ 2 คน');
    $('#result').textContent = d.winner
      ? (d.winner==='draw' ? 'กระดานเต็มแล้ว ทั้งสองฝ่ายเสมอ' : `ตานี้ชนะด้วยเส้นของ ${d.winner}`)
      : 'ยังไม่จบเกม';

    renderBoard(d.board || Array(9).fill(null), d.winner, d.turn);
  }

  function startPolling(){ clearInterval(poller); poller=setInterval(refresh, 1100); refresh(); }

  // Events
  $('#btnCreate').addEventListener('click', createRoom);
  $('#btnJoin').addEventListener('click', joinRoom);
  $('#btnReset').addEventListener('click', resetBoard);
  $('#btnHelp').addEventListener('click', (e)=>{e.preventDefault(); alert('วิธีเล่น: คนแรกที่วางได้เรียง 3 ตัว (แนวตั้ง/แนวนอน/แนวทแยง) ชนะ'); });

  // เตรียมกริดเริ่มต้น
  renderBoard(Array(9).fill(null), null, null);
</script>
</body>
</html>
