<?php
declare(strict_types=1);
// セッション開始（CSRFトークンの検証用）
session_start();

// セキュリティ検証：CSRFトークンが一致するか？
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid Security Token.');
}

// データの取得と整形
$lang  = strtolower($_POST['lang'] ?? 'etc');
$comment = $_POST['comment'] ?? 'none';
$code  = $_POST['code'] ?? '';

$now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
$date = $now->format('Y-m-d H:i:s');
$today = $now->format('Y-m-d');
$yearMonth = $now->format('Y-m');
$year = $now->format('Y');
$id = uniqid(); // 削除するためにIDを発行


$uid = "guestUser"; // FirebaseのGoogleログインを導入

// コードが空の場合は保存せずに戻る
if (empty(trim($code))) {
    // header('Location: index.php');
    header("Location: index.php?l=" . urlencode($lang));
    exit;
}

// 保存用の配列データを作成
$newData = [
    'id'    => $id,    
    "uid"   => $uid,
    'date'  => $date,       // 時間
    'lang'  => $lang,       // 言語
    'code'  => $code,       // コード
    'comment' => $comment   // コメント
];


// 1000件制限付きの月別保存
$fileSeq = 1; // ファイルの連番（1番からスタート）
$maxPerFile = 1000; // 1ファイルあたりの最大保存件数

// JSONファイルへの保存
$dir = 'data';
// $jsonFile = $dir . '/data.json';


// フォルダがなければ作成（権限0777）
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

while (true) {
    $jsonFile = $dir . "/archive_{$yearMonth}_{$fileSeq}.json";

    //もしファイルが存在しなければ、新しいファイルに
    if(!file_exists($jsonFile)){
        break;
    }


    // 既存のデータを読み込む
    $currentData = [];
    if (file_exists($jsonFile)) {
        $currentData = json_decode(file_get_contents($jsonFile), true) ?: [];

    // もし最大保存件数未満なら、このファイルに追記できる    
    if (count($currentData) < $maxPerFile){
        break;
    } 
    
    $fileSeq++;
}
}
// 新しいデータを追加
$currentData[] = $newData;

// JSON形式にしてファイルに書き込む（LOCK_EXで同時書き込みを防止）
// JSON_PRETTY_PRINT: 人間が見やすい形式にする
// JSON_UNESCAPED_UNICODE: 日本語が文字化けしないようにする
file_put_contents($jsonFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);


// 1000件制限付きの年別保存
$statsSeq = 1; // ファイルの連番（1番からスタート）
$limit = 1000; // 1ファイルあたりの最大保存件数

$statEntry = [
    'id'   => $id,   // 削除時に特定できるよう投稿IDと合わせる
    "uid"   => $uid,
    'date' => $today, 
    'lang' => $lang
];


while (true) {
    $statsFile = $dir . "/stats_{$year}_{$statsSeq}.json";

    //もしファイルが存在しなければ、新しいファイルに
    if(!file_exists($statsFile)){
        break;
    }


    // 既存のデータを読み込む
    $currentStats = [];
    if (file_exists($statsFile)) {
        $currentStats = json_decode(file_get_contents($statsFile), true) ?: [];

    // もし最大保存件数未満なら、このファイルに追記できる    
    if (count($currentStats) < $limit){
        break;
    } 
    
    $statasSeq++;
}
}


$currentStats[] = $statEntry;

file_put_contents($statsFile, json_encode($currentStats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);





// index.phpへリダイレクト（元の画面に戻る）
// header('Location: index.php');

// リダイレクト時に lang パラメータを付与する
header("Location: index.php?l=" . urlencode($lang));
exit;

// PHPのみのファイルなので ? > は書かない