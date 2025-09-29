// Основные функции
document.addEventListener('DOMContentLoaded', function() {
    initSmoothScroll();
    initFormValidation();
    initScrollEffects();
    initMobileNavigation();
    initAnimations();
    initFloatingButtons(); // Добавляем плавающие кнопки
});

// Плавная прокрутка
function initSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#') return;
            
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const headerHeight = document.querySelector('header').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                closeMobileMenu();
            }
        });
    });
}

// Валидация формы
function initFormValidation() {
    const form = document.getElementById('contactForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            const formData = new FormData(form);
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                message: formData.get('message')
            };
            
            try {
                // Отправляем на серверную функцию Netlify
                const response = await fetch('/.netlify/functions/send-to-telegram', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    showNotification('Сообщение отправлено! Я свяжусь с вами в ближайшее время.', 'success');
                    form.reset();
                } else {
                    throw new Error(result.error || 'Ошибка отправки');
                }
            } catch (error) {
                showNotification('Ошибка отправки сообщения. Попробуйте позже.', 'error');
                console.error('Ошибка отправки:', error);
            }
        }
    });
    
    // Валидация в реальном времени
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
}

// Валидация формы
function validateForm() {
    const form = document.getElementById('contactForm');
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.getAttribute('name');
    let isValid = true;
    
    clearFieldError(field);
    
    if (!value) {
        showFieldError(field, 'Это поле обязательно для заполнения');
        isValid = false;
    } else if (fieldName === 'email' && !isValidEmail(value)) {
        showFieldError(field, 'Введите корректный email адрес');
        isValid = false;
    } else if (fieldName === 'name' && value.length < 2) {
        showFieldError(field, 'Имя должно содержать минимум 2 символа');
        isValid = false;
    } else if (fieldName === 'message' && value.length < 10) {
        showFieldError(field, 'Сообщение должно содержать минимум 10 символов');
        isValid = false;
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorElement = field.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('error-message')) {
        errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('error');
    
    const errorElement = field.nextElementSibling;
    if (errorElement && errorElement.classList.contains('error-message')) {
        errorElement.remove();
    }
}

// Эффекты при скролле
function initScrollEffects() {
    let lastScrollTop = 0;
    const header = document.querySelector('header');
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Прячем/показываем header при скролле
        if (scrollTop > lastScrollTop && scrollTop > 200) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        lastScrollTop = scrollTop;
        
        // Добавляем тень header при скролле
        if (scrollTop > 50) {
            header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        } else {
            header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.05)';
        }
        
        // Показываем/скрываем кнопку "Наверх"
        toggleBackToTopButton();
        
        animateOnScroll();
    });
}

function animateOnScroll() {
    const elements = document.querySelectorAll('.project-card, .about-content, .contact-item');
    const windowHeight = window.innerHeight;
    
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < windowHeight - elementVisible) {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }
    });
}

// Мобильная навигация
function initMobileNavigation() {
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (!navToggle || !navLinks) return;
    
    navToggle.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        this.classList.toggle('active');
    });
    
    // Закрытие меню при клике на ссылку
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });
    
    // Закрытие при клике вне меню
    document.addEventListener('click', function(e) {
        if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
            closeMobileMenu();
        }
    });
}

function closeMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    const navToggle = document.querySelector('.nav-toggle');
    
    if (navLinks) navLinks.classList.remove('active');
    if (navToggle) navToggle.classList.remove('active');
}

// Анимации
function initAnimations() {
    const animatedElements = document.querySelectorAll('.project-card, .about-content, .contact-item');
    
    animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    
    // Запускаем анимацию после загрузки
    setTimeout(() => {
        animateOnScroll();
    }, 100);
}

// Плавающие кнопки
function initFloatingButtons() {
    // Создаем кнопку "Наверх"
    const backToTopBtn = document.createElement('button');
    backToTopBtn.id = 'backToTop';
    backToTopBtn.className = 'floating-btn back-to-top';
    backToTopBtn.innerHTML = '↑';
    backToTopBtn.title = 'Наверх';
    backToTopBtn.setAttribute('aria-label', 'Наверх');
    
    // Создаем плавающую кнопку "Связаться"
    const contactBtn = document.createElement('button');
    contactBtn.id = 'floatingContact';
    contactBtn.className = 'floating-btn contact-btn';
    contactBtn.innerHTML = '💬';
    contactBtn.title = 'Связаться со мной';
    contactBtn.setAttribute('aria-label', 'Связаться со мной');
    
    // Добавляем кнопки в body
    document.body.appendChild(backToTopBtn);
    document.body.appendChild(contactBtn);
    
    // События для кнопок
    backToTopBtn.addEventListener('click', scrollToTop);
    contactBtn.addEventListener('click', scrollToContact);
    
    // Изначально скрываем кнопку "Наверх"
    backToTopBtn.style.opacity = '0';
    backToTopBtn.style.visibility = 'hidden';
}

// Прокрутка наверх
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Прокрутка к контактам
function scrollToContact() {
    const contactSection = document.getElementById('contact');
    if (contactSection) {
        const headerHeight = document.querySelector('header').offsetHeight;
        const targetPosition = contactSection.offsetTop - headerHeight;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }
}

// Показать/скрыть кнопку "Наверх"
function toggleBackToTopButton() {
    const backToTopBtn = document.getElementById('backToTop');
    if (!backToTopBtn) return;
    
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    if (scrollTop > 300) {
        backToTopBtn.style.opacity = '1';
        backToTopBtn.style.visibility = 'visible';
    } else {
        backToTopBtn.style.opacity = '0';
        backToTopBtn.style.visibility = 'hidden';
    }
}

// Уведомления
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Показываем уведомление
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Скрываем через 3 секунды
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}