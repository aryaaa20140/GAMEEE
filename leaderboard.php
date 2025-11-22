<?php
// Simple leaderboard that stores scores in a local JSON file (leaderboard.json).
// Security notes: this is a simple demo. For production, validate inputs more strictly and use a DB.

$file = _DIR_ . '/leaderboard.json';
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    // return top scores
    $top = [];
    if(file_exists($file)){
        $data = json_decode(file_get_contents($file), true);
        if(is_array($data)){
            usort($data, function($a,$b){ return $b['score'] - $a['score']; });
            $top = array_slice($data, 0, 10);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['top'=>$top]);
    exit;
}

// POST: expected JSON {name, score}
$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if(!is_array($in)){
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid-json']);
    exit;
}
$name = preg_replace('/[^\w\s-]/u', '', trim(substr($in['name'] ?? 'Player', 0, 30)));
$score = intval($in['score'] ?? 0);
if($score < 0) $score = 0;
$entry = ['name'=>$name, 'score'=>$score, 'ts'=>time()];

// load existing
$data = [];
if(file_exists($file)){
    $d = json_decode(file_get_contents($file), true);
    if(is_array($d)) $data = $d;
}
// append and keep at most 1000 entries
$data[] = $entry;
if(count($data) > 1200) $data = array_slice($data, -1000);
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
