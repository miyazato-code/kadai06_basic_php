<?php
declare(strict_types=1);
// セッションを開始して、CSRF（不正送信）対策の準備
session_start();

// セキュリティ1：CSRFトークン（合言葉）の生成
if (!isset($_SESSION['csrf_token'])) {
    // ランダムな32文字の安全な文字列を生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// セキュリティ2：XSS対策用の関数（HTMLエスケープ）
// 表示する前に「<」などの記号を安全な文字に変換
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// データの読み込み
// $jsonFile = 'data/data.json';
// $dataItems = [];
// if (file_exists($jsonFile)) {
    // JSONファイルの中身を読み込み、PHPの「連想配列」に変換
    // $jsonData = file_get_contents($jsonFile);
    // $dataItems = json_decode($jsonData, true) ?: [];
    // 最新のものが上にくるように、配列を逆順に
    // $dataItems = array_reverse($dataItems);
// }

$jsonFile = glob('data/archive_*.json');
$allDataItems = [];
$dataItems = [];

if ($jsonFile) {

    sort($jsonFile);

    foreach ($jsonFile as $jsonFile) {
        if (is_readable($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $decodedData = json_decode($jsonContent, true);

            if (is_array($decodedData)) {
                $allDataItems = array_merge($allDataItems, $decodedData);
            }
        }
    }
}

$dataItems = array_reverse($allDataItems);

// 表示件数の制限（簡易ペジネーション：最新x件のみ）
$displayItems = array_slice($dataItems, 0, 1000);


$statFiles = glob('data/stats_*.json');
$dailyCounts = [];
$langCounts = [];

if ($statFiles) {
    foreach ($statFiles as $sf) {
        $logs = json_decode(file_get_contents($sf), true) ?: [];
        foreach ($logs as $log) {
            $d = $log['date'];
            $l = $log['lang'];

            // 日付ごとの集計
            $dailyCounts[$d] = ($dailyCounts[$d] ?? 0) + 1;
            // 言語ごとの集計
            $langCounts[$l] = ($langCounts[$l] ?? 0) + 1;
        }
    }
}

// URLのパラメータを受け取り、対応するボタンに is-active クラスを付与
$active_lang = $_GET['active_lang'] ?? 'all';

?>

<!--                  _         ____               -->
<!--   ___  ___    __| | _   _ |  _ \   ___ __  __ -->
<!--  / __|/ _ \  / _` || | | || | | | / _ \\ \/ / -->
<!-- | (__| (_) || (_| || |_| || |_| ||  __/ >  <  -->
<!--  \___|\___/  \__,_| \__, ||____/  \___|/_/\_\ -->
<!--                     |___/                     -->


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    <!-- 許可されたソース以外のスクリプト実行を制限し、XSS攻撃を防止するセキュリティ設定 -->
    <title>codyDex</title>
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="l-main">

    <div class="l-container">

        <nav class="c-tabs col-12">
            <div class="c-tabs__inner">
                <button class="c-tabs__item <?= $active_lang == 'all' ? 'is-active' : '' ?>" lang-filter="all" shorten="cody">codyDex</button>
                <button class="c-tabs__item <?= $active_lang == 'html' ? 'is-active' : '' ?>" lang-filter="html" shorten="HTML">HTML</button>
                <button class="c-tabs__item <?= $active_lang == 'css' ? 'is-active' : '' ?>" lang-filter="css" shorten="CSS">CSS</button>
                <button class="c-tabs__item <?= $active_lang == 'js' ? 'is-active' : '' ?>" lang-filter="js" shorten="JS">JavaScript</button>
                <button class="c-tabs__item <?= $active_lang == 'php' ? 'is-active' : '' ?>" lang-filter="php" shorten="PHP">PHP</button>
                <button class="c-tabs__item <?= $active_lang == 'py' ? 'is-active' : '' ?>" lang-filter="py" shorten="Py">Python</button>
                <button class="c-tabs__item <?= $active_lang == 'ty' ? 'is-active' : '' ?>" lang-filter="ty" shorten="TS">TypeScript</button>

            </div>
        </nav>

        <main class="c-list" id="js-list">
          
                <?php foreach ($displayItems as $item): ?>

                    <article class="c-card" code-lang="<?= h($item['lang']) ?>">

                        <!-- <form action="delete.php" method="post" class="c-card__delete-form"> -->
                            <!-- <input type="hidden" name="id" value="<?= h($item['id']) ?>"> -->
                            <!-- <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"> -->
                            <!-- <button type="submit" class="c-card__delete-btn" title="削除" onclick="return confirm('削除しますか？')"> -->
                                <!-- <span class="c-card__delete-icon"></span> -->
                            <!-- </button> -->
                        <!-- </form> -->

                        <div class="c-card__delete-form">
                            <button type="button" class="c-card__delete-btn" title="削除" onclick="openDeleteModal('<?= h($item['id']) ?>')">
                                <span class="c-card__delete-icon"></span>
                            </button>
                        </div>

                        <header class="c-card__header">
                            <span class="c-card__tag"><?= h(strtoupper($item['lang'])) ?></span>
                            <!-- <span class="c-card__time"><?= h($item['date']) ?></span> -->
                             <time class="c-card__time js-relative-time" datetime="<?= h($item['date']) ?>" data-time="<?= h($item['date']) ?>">
                                <?= h($item['date']) ?>
                             </time>
                        </header>

                        <div class="c-card__body">
                            <div class="c-card__code-wrapper">
                                <pre class="c-card__code"><code><?= h($item['code']) ?></code></pre>
                            </div>
                        
                            <div class="c-card__footer">
                                <p class="c-card__comment">// <?= h($item['comment']) ?></p>
                            </div>
                        </div>

                    </article>

                <?php endforeach; ?>
        </main>

        <footer class="c-search col-12">
            <form action="save.php" method="post" id="js-form" class="c-search__form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="c-search__box">
                    <textarea name="code" id="js-code" class="c-search__input" placeholder="コードを書く" tabindex="1" required></textarea>
                    
                    <div class="c-search__actions">
                        <div class="c-search__actions-left">
                            <select name="lang" id="js-select" class="c-search__select" tabindex="2">
                                <option value="html">HTML</option>
                                <option value="css">CSS</option>
                                <option value="js">JavaScript</option>
                                <option value="php">PHP</option>
                                <option value="py">Python</option>
                                <option value="ty">TypeScript</option>
                            </select>
                            <input type="text" name="comment" class="c-form__input-comment" placeholder="// コメントアウト" autocomplete="off" tabindex="3" required>
                        </div>
                        <button type="submit" class="c-search__submit" tabindex="4">COMMIT<span class="kbd"></span></button>
                    </div>
                </div>
            </form>
        </footer>
    </div>




    <dialog id="js-delete-modal" class="c-modal">
        <div class="c-modal__inner">
            <h3 class="c-modal__title">このコードを削除しますか？</h3>
            <p class="c-modal__text">これにより、保存データから<br>完全に削除されます。</p>
        
            <form action="delete.php" method="post" id="js-delete-form">
                <input type="hidden" name="id" id="js-delete-id">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="active_lang" id="js-delete-active-lang" value="all">
            
                <div class="c-modal__actions">
                    <button type="button" class="c-btn c-btn--secondary" onclick="closeDeleteModal()">cancel</button>
                    <button type="submit" class="c-btn c-btn--danger">delete</button>
                </div>
            </form>
        </div>
    </dialog>

    <div id="js-toast" class="c-toast">削除しました</div>
    
    <script src="assets/js/time-util.js"></script>
    <script src="assets/js/main.js"></script>

</body>
</html>