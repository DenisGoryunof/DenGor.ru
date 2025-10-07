// Маркетинговые функции и аналитика

// Отслеживание событий для РСЯ и аналитики
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
    
    // Отправка в dataLayer для дополнительной аналитики
    if (typeof dataLayer !== 'undefined') {
        dataLayer.push({
            event: 'RSY_' + eventName,
            ...params
        });
    }
}

// Отслеживание показов рекламы
function initializeAdTracking() {
    // Создаем observer для отслеживания видимости рекламных блоков
    const adObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const adId = entry.target.id;
                trackRSY('ad_impression', {
                    ad_block: adId,
                    ad_location: entry.target.dataset.location || 'unknown'
                });
                adObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    // Наблюдаем за всеми рекламными блоками
    document.querySelectorAll('[id^="yandex_rtb_"]').forEach(ad => {
        adObserver.observe(ad);
    });
}

// Отслеживание навигации
function trackNavigation(section) {
    trackRSY('navigation', { section: section });
    
    // Дополнительная аналитика навигации
    if (typeof dataLayer !== 'undefined') {
        dataLayer.push({
            event: 'navigation',
            section: section,
            timestamp: Date.now()
        });
    }
}

// Отслеживание проектов
function trackProjectView(projectName, projectType) {
    trackRSY('project_view', {
        project_name: projectName,
        project_type: projectType
    });
}

// Отслеживание кликов по проектам
function trackProjectClick(projectName, projectType, linkType) {
    trackRSY('project_click', {
        project_name: projectName,
        project_type: projectType,
        link_type: linkType // 'github', 'demo', 'case'
    });
}

// Отслеживание формы
function trackFormSubmission(status, formType = 'contact') {
    trackRSY('form_submission', {
        status: status,
        form_type: formType
    });
}

// Отслеживание социальных сетей
function trackSocialClick(network) {
    trackRSY('social_click', {
        network: network
    });
}

// Отслеживание времени на странице
function trackTimeSpent() {
    let pageStartTime = Date.now();
    let maxScroll = 0;

    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        maxScroll = Math.max(maxScroll, currentScroll);
    });

    window.addEventListener('beforeunload', function() {
        const timeSpent = Math.floor((Date.now() - pageStartTime) / 1000);
        trackRSY('page_time_spent', {
            time_seconds: timeSpent,
            max_scroll: maxScroll
        });
    });
}

// Анимации при скролле
function initializeAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Отслеживание видимости элементов
                if (entry.target.classList.contains('showcase-item')) {
                    const projectName = entry.target.querySelector('.showcase-title')?.textContent || 'unknown';
                    trackProjectView(projectName, 'showcase');
                }
            }
        });
    }, observerOptions);

    // Наблюдение за элементами
    document.querySelectorAll('.showcase-item, .benefit-card, .process-step, .pricing-card, .testimonial-card').forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
    });
}

// FAQ функциональность
function initializeFAQ() {
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Закрываем все FAQ
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Открываем текущий если был закрыт
            if (!isActive) {
                faqItem.classList.add('active');
                const questionText = this.querySelector('h3').textContent;
                trackRSY('faq_open', { question: questionText });
            }
        });
    });
}

// Плавная прокрутка с отслеживанием
function initializeSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.querySelector(this.getAttribute('href'));
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
                trackNavigation(targetId);
            }
        });
    });
}

// Динамическая загрузка GitHub статистики
async function loadGitHubStats() {
    try {
        const response = await fetch('https://api.github.com/users/DenisGoryunof');
        const data = await response.json();
        
        // Обновление статистики
        const stats = document.querySelector('.github-stats');
        if (stats) {
            stats.innerHTML = `
                <div class="stat-item">
                    <span class="stat-number">${data.public_repos}</span>
                    <span class="stat-label">Репозиториев</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${data.followers}</span>
                    <span class="stat-label">Подписчиков</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${new Date(data.created_at).getFullYear()}</span>
                    <span class="stat-label">На GitHub</span>
                </div>
            `;
        }
        
        trackRSY('github_stats_loaded', {
            repos: data.public_repos,
            followers: data.followers
        });
    } catch (error) {
        console.log('GitHub stats loading error:', error);
    }
}

// Инициализация всех маркетинговых функций
document.addEventListener('DOMContentLoaded', function() {
    initializeAdTracking();
    initializeAnimations();
    initializeFAQ();
    initializeSmoothScroll();
    loadGitHubStats();
    trackTimeSpent();
    
    // Отслеживание загрузки страницы
    trackRSY('page_load', {
        page_url: window.location.href,
        user_agent: navigator.userAgent
    });
});

// Экспорт функций для глобального использования
window.trackRSY = trackRSY;
window.trackProjectView = trackProjectView;
window.trackProjectClick = trackProjectClick;
window.trackSocialClick = trackSocialClick;
window.loadGitHubStats = loadGitHubStats;