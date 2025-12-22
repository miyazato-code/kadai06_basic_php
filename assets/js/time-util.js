/**
 * time-util.js
 * 日本語環境に最適化された可変粒度時間計算ロジック
 */

const TimeUtil = {
    /**
     * 日時文字列を日本語の相対時間に変換する
     * @param {string} dateString 
     * @returns {string} 
     */
    getRelativeTime: function(dateString) {
        const now = new Date();
        const past = new Date(dateString);

        if (isNaN(past.getTime())) return dateString;

        const diffInSeconds = Math.floor((now - past) / 1000);

        // 1分未満
        if (diffInSeconds < 60) return 'たった今';
        
        // 1時間未満
        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) return `${diffInMinutes}分前`;
        
        // 24時間未満
        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `${diffInHours}時間前`;
        
        // 1週間未満
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) {
            if (diffInDays === 1) return '昨日';
            if (diffInDays === 2) return '一昨日';
            return `${diffInDays}日前`;
        }
        
        // 1週間以上（可変粒度）
        const isSameYear = now.getFullYear() === past.getFullYear();
        if (isSameYear) {
            // 今年なら月日と時間のみ
            return past.toLocaleDateString('ja-JP', {
                month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'
            });
        } else {
            // 昨年以前なら年月日を表示
            return past.toLocaleDateString('ja-JP', {
                year: 'numeric', month: '2-digit', day: '2-digit'
            });
        }
    },

    /**
     * 指定されたセレクタの要素をすべて更新する
     * @param {string} selector 
     */
    updateAll: function(selector = '.js-relative-time') {
        document.querySelectorAll(selector).forEach(el => {
            const rawDate = el.getAttribute('data-time');
            if (rawDate) {
                el.textContent = this.getRelativeTime(rawDate);
            }
        });
    }
};