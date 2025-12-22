<?php
declare(strict_types=1); // 厳格な型判定モードを有効化
session_start(); // CSRFトークンチェックのためにセッションを開始

// ------------------------------------------------------------
// 1. セキュリティ検証
// ------------------------------------------------------------
// POSTメソッド以外でのアクセスを遮断
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid Request Method');
}

// セッション内のトークンと送信されたトークンが一致するか検証
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid Token: セッションが切れたか、不正なアクセスです。');
}

// ------------------------------------------------------------
// 2. 削除対象IDの取得
// ------------------------------------------------------------
// フォームから送信されたIDを取得（空なら処理中断）
$targetId = $_POST['id'] ?? '';
if (empty($targetId)) {
    die('削除対象のIDが指定されていません。');
}

/**
 * 【重要】分割された複数のJSONファイルから特定のIDを探して削除する関数
 * * @param string $pattern glob用のファイル検索パターン (例: 'data/archive_*.json')
 * @param string $id 削除したい要素のID
 */
function deleteIdFromJsonFiles(string $pattern, string $id): void {
    // パターンに一致するファイルをすべて取得
    $files = glob($pattern);

    if (!$files) return;

    foreach ($files as $filePath) {
        // 1. ファイルの中身を読み込む
        $jsonData = file_get_contents($filePath);
        $items = json_decode($jsonData, true) ?: [];

        // 2. IDが一致しないものだけを抽出（＝一致するものを削除）
        $initialCount = count($items); // 削除前の件数
        $filteredItems = array_filter($items, function($item) use ($id) {
            return $item['id'] !== $id;
        });

        // 3. もし件数が変わっていたら（＝対象が見つかって削除されたら）保存
        if (count($filteredItems) !== $initialCount) {
            // array_valuesで配列の添字(0,1,2...)を綺麗に振り直す
            file_put_contents(
                $filePath, 
                json_encode(array_values($filteredItems), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 
                LOCK_EX
            );
            // 1つのIDは1箇所にしか存在しない前提なら、ここで終了(return)してもOK
        }
    }
}

// ------------------------------------------------------------
// 3. 実際の削除実行
// ------------------------------------------------------------

// (A) 投稿データ (archive_2025-12_1.json など) から削除
deleteIdFromJsonFiles('data/archive_*.json', $targetId);

// (B) 統計データ (stats_2025_1.json など) から削除
deleteIdFromJsonFiles('data/stats_*.json', $targetId);

// ------------------------------------------------------------
// 4. 完了後のリダイレクト
// ------------------------------------------------------------


// 非同期通信か判断
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // JS側に成功を伝える（リダイレクトはしない）
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

$active_lang = $_POST['active_lang'] ?? 'all';
// 削除完了のステータスを付けてトップページへ戻る
// header('Location: index.php?status=deleted');
header("Location: index.php?active_lang=" . urlencode($active_lang));
exit;