// Yandex.RTB рекламные блоки с вашими ID

// Основной RTB код
(function(w, d, n, s, t) {
    w[n] = w[n] || [];
    w[n].push(function() {
        Ya.Context.AdvManager.render({
            blockId: "R-A-11815532-1",
            renderTo: "yandex_rtb_R-A-11815532-1",
            type: "feed"
        });
        
        // Отслеживание показа рекламы
        trackRSY('ad_rendered', {
            block_id: 'R-A-11815532-1',
            ad_type: 'feed'
        });
    });
    t = d.getElementsByTagName("script")[0];
    s = d.createElement("script");
    s.type = "text/javascript";
    s.src = "https://yandex.ru/ads/system/context.js";
    s.async = true;
    t.parentNode.insertBefore(s, t);
})(this, this.document, "yandexContextCb");

// Функция отслеживания РСЯ
function trackRSY(eventName, params = {}) {
    if (typeof ym !== 'undefined') {
        ym(88475619, 'reachGoal', 'RSY_' + eventName, {
            ...params,
            timestamp: Date.now(),
            page_category: 'marketing_portfolio'
        });
    }
    
    // Дополнительная аналитика
    console.log('RSY Event:', eventName, params);
}

// Отслеживание кликов по рекламе
function trackAdClick(adId, adType) {
    trackRSY('ad_click', {
        ad_id: adId,
        ad_type: adType
    });
}

// Инициализация отслеживания рекламы при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Отслеживание кликов на рекламные блоки
    document.querySelectorAll('[id^="yandex_rtb_"]').forEach(ad => {
        ad.addEventListener('click', function() {
            const adId = this.id;
            const adType = this.dataset.adType || 'unknown';
            trackAdClick(adId, adType);
        });
    });
});

// Экспорт функций
window.trackRSY = trackRSY;
window.trackAdClick = trackAdClick;