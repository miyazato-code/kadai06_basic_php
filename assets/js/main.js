// --- 1. 定数・言語パターンの定義 ---
// 言語判定に使用する正規表現（プログラミング言語の特徴的な記号を判別します）
const languagePatterns = {
    // PHP: 開始タグ、変数($)、名前空間、アクセス修飾子、アロー演算子、echo
    php: /<\?php|\$[\w]+|namespace\s+[\w\\]+|public\s+function\s+|->|echo\s+/i,

    // Python: 関数定義、インポート、制御構文、self、メインブロック、デコレータ
    python: /\b(def|import|from|as|elif|None|True|False)\b|print\(|self\.|if\s+__name__\s+==|@[\w]+/i,

    // TypeScript: 型定義(interface/type/enum)、型アノテーション(: string等)、アクセス修飾子
    typescript: /\b(interface|type|enum)\s+\w+|:\s+(string|number|boolean|any|void)\b|<\w+>|private\s+\w+:|readonly\s+/i,

    // JavaScript: ES6以降の宣言、アロー関数、コンソール出力、イベントリスナ、DOM操作、JSON
    javascript: /\b(const|let|var)\b|=>|console\.log\(|addEventListener\(|document\.get|JSON\.(parse|stringify)/i,

    // CSS: セレクタ構造、プロパティ定義、メディアクエリ、変数
    css: /[\.#][\w-]+\s*\{|[\w-]+\s*:\s*[^;]+;|@media|@keyframes|calc\(|var\(--/i,

    // HTML: ドキュメント宣言、主要なタグ、class/id属性
    html: /<!DOCTYPE\s+html>|<(?:html|head|body|div|span|script|link|meta|input|form|br)\b|class=["'].*?["']|id=["'].*?["']/i

};

// ローカルストレージ（下書き保存用）のキー名
const STORAGE_KEY = 'codydex_draft';

// --- 2. ユーティリティ関数の定義 ---

/**
 * トースト通知を表示する
 * @param {string} message - 表示するメッセージ
 */
function showToast(message) {
    const toast = document.querySelector('#js-toast');
    if (!toast) return;
    
    toast.textContent = message; // メッセージを注入
    toast.classList.add('is-show'); // 表示クラスを追加
    
    // 3秒後に非表示にする
    setTimeout(() => {
        toast.classList.remove('is-show');
    }, 3000);
}

/**
 * デバウンス関数：短時間に連続して発生するイベント（入力など）の実行を、
 * 最後の発生から指定時間（delay）待ってから1回だけ実行するように制限します。
 */
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        if (timeoutId) clearTimeout(timeoutId); // 前回の予約をキャンセル
        timeoutId = setTimeout(() => {
            func.apply(this, args); // 指定時間後に本番の関数を実行
        }, delay);
    };
}

/**
 * 言語自動推測関数：入力されたコードの内容から言語を判定し、セレクトボックスを更新します。
 */
function performAutoDemand(textarea) {
    const code = textarea.value;
    const selectEl = document.querySelector('#js-select'); // HTML内のセレクトボックスを取得
    const priorityOrder = ['php', 'python', 'typescript', 'javascript', 'css', 'html'];
    let detectedLang = '';

    // 優先順位が高い順に、コードの中に特徴的な文字があるかテストします
    for (const lang of priorityOrder) {
        if (languagePatterns[lang].test(code)) {
            detectedLang = lang;
            break; // 1つ見つかったらループを抜ける
        }
    }

    if (detectedLang && selectEl) {
        // TypeScriptの場合、HTMLのvalue値 'ty' に変換してセット
        const targetValue = (detectedLang === 'typescript') ? 'ty' : detectedLang;

        if (selectEl.value !== targetValue) {
            selectEl.value = targetValue;
            // 視覚効果：一瞬だけ光らせて「自動で切り替わったこと」を伝えます
            selectEl.style.transition = "outline 0.3s";
            selectEl.style.outline = "2px solid var(--color-accent)";
            setTimeout(() => { selectEl.style.outline = "none"; }, 500);
        }
    }
}

/**
 * タブの中央スクロール関数：選択されたタブが常に画面の中央にくるよう調整します。
 */
const scrollToActiveTab = (tabElement) => {
    if (!tabElement) return;
    tabElement.scrollIntoView({
        behavior: 'smooth', // ぬるっと動かす
        block: 'nearest',   // 縦方向は動かさない
        inline: 'center'    // 横方向の中央に
    });
};

/**
 * フィルタリング実行関数：特定の言語のカードだけを表示し、他を非表示にします。
 */
const applyFilter = (filter) => {
    const cards = document.querySelectorAll('.c-card');
    cards.forEach(card => {
        const cardLang = card.getAttribute('code-lang');
        // 'all' か、カードの言語がフィルタと一致していれば表示
        if (filter === 'all' || cardLang === filter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
};

//URLパラメータを更新する関数
const updateUrlParam = (filter) => {
    const url = new URL(window.location.origin + window.location.pathname);
    // 常に l だけをセットしたクリーンなURLを作成
    url.searchParams.set('l', filter);
    // ブラウザの履歴を更新（ページ遷移はしない）
    window.history.replaceState({}, '', url.toString());
};

/**
 * 削除モーダル表示
 */
function openDeleteModal(id) {
    const modal = document.querySelector('#js-delete-modal');
    const idInput = document.querySelector('#js-delete-id');
    const langInput = document.querySelector('#js-delete-active-lang');

    if (!modal || !idInput) return;

    // 削除対象のIDをセット
    idInput.value = id;

    // 現在アクティブなタブの lang-filter を取得してセット
    const activeTab = document.querySelector('.c-tabs__item.is-active');
    if (activeTab && langInput) {
        langInput.value = activeTab.getAttribute('lang-filter');
    }

    modal.showModal();
}

/**
 * 削除モーダルを閉じる
 */
function closeDeleteModal() {
    document.querySelector('#js-delete-modal')?.close();
}

// --- 3. メイン初期化処理 ---
document.addEventListener('DOMContentLoaded', () => {

    // 要素の取得
    const codeInput = document.querySelector('#js-code');
    const langSelect = document.querySelector('#js-select');
    const commentInput = document.querySelector('.c-form__input-comment');
    const form = document.querySelector('#js-form');
    const tabs = document.querySelectorAll('.c-tabs__item');
    const submitBtn = document.querySelector('.c-search__submit');
    const deleteModal = document.querySelector('#js-delete-modal');
    // const deleteIdInput = document.querySelector('#js-delete-id');
    // const toast = document.querySelector('#js-toast');
    const deleteForm = document.querySelector('#js-delete-form'); // 削除フォームの取得

    if (deleteForm) {
        deleteForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // 通常の送信(ページ遷移)をキャンセル

            const formData = new FormData(deleteForm);
            const targetId = formData.get('id');

            try {
                // 背景で delete.php を実行
                const response = await fetch('delete.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.ok) {
                    closeDeleteModal(); // モーダルを閉じる
                    
                    // カードを滑らかに消すアニメーション
                    const card = document.querySelector(`.c-card[data-id="${targetId}"]`);
                    if (card) {
                        card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95) translateY(10px)';
                        
                        // アニメーション完了後に要素を削除
                        setTimeout(() => {
                            card.remove();
                            // ここで通知を表示
                            showToast('削除しました'); 
                        }, 400);
                    }
                } else {
                    showToast('削除に失敗しました');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showToast('ネットワークエラーが発生しました');
            }
        });
    }

    // 下書き復元ロジック
    const savedData = JSON.parse(localStorage.getItem(STORAGE_KEY));
    if (savedData && codeInput) {
        codeInput.value = savedData.code || '';
        if (langSelect) langSelect.value = savedData.lang || 'html';
        if (commentInput) commentInput.value = savedData.comment || '';
        // 高さの初期調整
        codeInput.style.height = 'auto';
        codeInput.style.height = codeInput.scrollHeight + 'px';
    }

    // ページ読み込み時のフィルタ初期化（PHPからの指示 active_lang に従う）
    const initialActiveTab = document.querySelector('.c-tabs__item.is-active');
    if (initialActiveTab) {
        const initialFilter = initialActiveTab.getAttribute('lang-filter');
        applyFilter(initialFilter);
        // 初回ロード時はURLパラメータも同期（URLにない場合のため）
        updateUrlParam(initialFilter);
        setTimeout(() => scrollToActiveTab(initialActiveTab), 150); // 描画完了を待ってからスクロール
    }

    // イベント登録：入力エリア（自動保存 & 言語推測 & 高さ調整）
    if (codeInput) {
        const debouncedAutoDemand = debounce((el) => performAutoDemand(el), 500);

        codeInput.addEventListener('input', function () {
            // 高さ調整
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';

            // 下書き保存
            const data = {
                code: this.value,
                lang: langSelect?.value || 'html',
                comment: commentInput?.value || ''
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));

            // 言語推測（500ms入力が止まったら実行）
            debouncedAutoDemand(this);
        });
    }

    // イベント登録：タブクリック（フィルタリング & 移動）
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // クラスの付け替え
            document.querySelector('.c-tabs__item.is-active')?.classList.remove('is-active');
            tab.classList.add('is-active');

            const filter = tab.getAttribute('lang-filter');
            // 1. フィルタ適用
            applyFilter(filter);
            // 2. URLの書き換え
            updateUrlParam(filter);
            // 3. スクロール調整
            scrollToActiveTab(tab);

            // モバイル時は上に戻ってリストを見やすくする
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // イベント登録：キーボードショートカット
    document.addEventListener('keydown', (e) => {
        // Ctrl + Enter (または Cmd + Enter) で送信
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (codeInput?.value.trim() !== "") form?.submit();
        }
    });

    // イベント登録：フォーカストラップ（Tab移動のループ）
    submitBtn?.addEventListener('keydown', (e) => {
        if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            codeInput?.focus(); // COMMITの次はコード入力に戻る
        }
    });

    // イベント登録：言語選択のクイックキー (Selectフォーカス中)
    langSelect?.addEventListener('keydown', (e) => {
        const key = e.key.toLowerCase();
        const map = { 'h': 'html', 'c': 'css', 'j': 'js', 'p': 'php', 'y': 'py', 't': 'ty' };
        if (map[key]) {
            e.preventDefault();
            langSelect.value = map[key];
        }
    });

    // 送信時の処理（ストレージ削除）
    form?.addEventListener('submit', () => {
        // 送信直前に、現在のフィルタ状況を hidden フィールドに同期（もし必要なら）
        localStorage.removeItem(STORAGE_KEY);
    });

    // 時間表示の自動更新 (TimeUtilが外部にある前提)
    if (typeof TimeUtil !== 'undefined') {
        TimeUtil.updateAll();
        setInterval(() => TimeUtil.updateAll(), 60000);
        window.addEventListener('focus', () => TimeUtil.updateAll());
    }


});
