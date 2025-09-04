<?php
// api.php — Who Is It? backend (2 players, rooms)
// Storage: JSON per room in /data; character list in /data/people.json


header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');


$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) @mkdir($DATA_DIR, 0777, true);


function clean($s){ return preg_replace('/[^A-Za-z0-9_-]/','',$s ?? ''); }
function room_path($room){ global $DATA_DIR; return $DATA_DIR . '/room_' . clean($room) . '.json'; }
function load_room($room){ $p = room_path($room); if(!file_exists($p)) return null; return json_decode(file_get_contents($p), true); }
function save_room($room, $data){ $p = room_path($room); file_put_contents($p, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX); }
function rnd_token(){ return bin2hex(random_bytes(8)); }
function all_ids($PEOPLE){ return array_map(fn($p)=>$p['id'], $PEOPLE); }
function find_person_by_id($PEOPLE, $id){ foreach($PEOPLE as $p){ if($p['id']===$id) return $p; } return null; }


$PEOPLE_FILE = __DIR__ . '/data/people.json';
if (!file_exists($PEOPLE_FILE)) { http_response_code(500); echo json_encode(['error'=>'people.json missing']); exit; }
$PEOPLE = json_decode(file_get_contents($PEOPLE_FILE), true);
if(!is_array($PEOPLE)){ echo json_encode(['error'=>'bad people.json']); exit; }


$ALLOWED_ATTRS = [
'gender' => ['male','female'],
'hair' => ['black','brown','blonde','red','none'],
'glasses' => ['true','false'],
'facial_hair' => ['true','false'],
'hat' => ['true','false'],
'earrings' => ['true','false'],
'eye_color' => ['brown','blue','green'],
'skin_tone' => ['light','medium','dark'],
];


$action = $_GET['action'] ?? 'list';
$body = json_decode(file_get_contents('php://input'), true) ?: [];


function ensure_room_struct($room){
if(!$room) return null;
$room['qcount'] = $room['qcount'] ?? ['p1'=>0,'p2'=>0];
$room['events'] = $room['events'] ?? [];
$room['last_event_id'] = $room['last_event_id'] ?? 0;
return $room;
}
function add_event(&$room, $ev){ $room['last_event_id'] = ($room['last_event_id'] ?? 0) + 1; $ev['id'] = $room['last_event_id']; $ev['ts']=time(); $room['events'][] = $ev; if(count($room['events'])>80){ $room['events'] = array_slice($room['events'], -60); } }
function slot_from_token($room,$token){ if(!$token) return null; foreach(['p1','p2'] as $s){ if(isset($room['players'][$s]['token']) && $room['players'][$s]['token']===$token) return $s; } return null; }
function other($slot){ return $slot==='p1'?'p2':'p1'; }


switch($action){
case 'list':
echo json_encode(['people'=>$PEOPLE]);
break;


case 'create':{
$rid = clean($body['room'] ?? ''); $name = trim($body['name'] ?? 'Player');
if(!$rid){ echo json_encode(['error'=>'room id required']); break; }
$room = load_room($rid);
if($room && isset($room['players']['p1']) && isset($room['players']['p2'])){ echo json_encode(['error'=>'ห้องเต็มแล้ว']); break; }
if(!$room){
$room=[
'room'=>$rid,
'created_at'=>time(),
'players'=>[],
'turn'=>'p1',
'status'=>'waiting',
'qcount'=>['p1'=>0,'p2'=>0],
'events'=>[],
'last_event_id'=>0,
];
}
$tok = rnd_token();
$ids = all_ids($PEOPLE);
$secret = $ids[random_int(0,count($ids)-1)];
$room['players']['p1'] = [ 'name'=>$name, 'token'=>$tok, 'joined_at'=>time(), 'secret_id'=>$secret ];
add_event($room, ['type'=>'join','who'=>'p1']);
save_room($rid,$room);
echo json_encode(['ok'=>true,'slot'=>'p1','token'=>$tok]);
break; }
}