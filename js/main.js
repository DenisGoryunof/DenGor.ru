// Обработка формы с отправкой в Telegram
document.getElementById('protectedForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitButton = document.getElementById('submitButton');
    const formStatus = document.getElementById('formStatus');
    const rateLimitInfo = document.getElementById('rateLimitInfo');
    
    // Очистка предыдущих ошибок
    document.querySelectorAll('.error-message').forEach(msg => msg.style.display = 'none');
    document.querySelectorAll('.form-input').forEach(input => {
        input.classList.remove('error', 'success');
    });
    formStatus.style.display = 'none';
    rateLimitInfo.style.display = 'none';

    // Проверка rate limiting
    const rateLimitCheck = rateLimiter.canSubmit();
    if (!rateLimitCheck.canSubmit) {
        if (rateLimitCheck.reason === 'rate_limit') {
            const minutes = Math.ceil(rateLimitCheck.timeRemaining / 60000);
            rateLimitInfo.innerHTML = `⏱️ Слишком много попыток. Пожалуйста, подождите ${minutes} минут.`;
            rateLimitInfo.style.display = 'block';
            trackRSY('form_rate_limited');
            return;
        }
    }

    // Получение данных формы
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Валидация honeypot
    if (data.honeypot || data.email_confirm) {
        showError('Обнаружена подозрительная активность');
        return;
    }

    // Валидация времени
    const timeSpent = Date.now() - parseInt(data.timestamp);
    const timeIssues = ValidationUtils.validateTimeSpent(timeSpent);
    if (timeIssues.length > 0) {
        showError(timeIssues[0]);
        trackRSY('form_time_validation_failed');
        return;
    }

    // Базовая валидация
    const errors = {};
    
    if (!data.name || data.name.length < 2) {
        errors.name = 'Имя должно содержать минимум 2 символа';
    }
    
    if (!data.email || !ValidationUtils.validateEmail(data.email)) {
        errors.email = 'Введите корректный email адрес';
    }
    
    if (data.phone && !ValidationUtils.validatePhone(data.phone)) {
        errors.phone = 'Введите корректный номер телефона';
    }

    if (!data.service) {
        errors.service = 'Выберите интересующую услугу';
    }
    
    if (!data.message || data.message.length < 10) {
        errors.message = 'Сообщение должно содержать минимум 10 символов';
    }
    
    if (data.message.length > 1000) {
        errors.message = 'Сообщение слишком длинное (максимум 1000 символов)';
    }

    // Проверка спама
    const spamIssues = ValidationUtils.detectSpam(data.message);
    if (spamIssues.length > 0) {
        errors.message = spamIssues.join(', ');
        trackRSY('form_spam_detected');
    }

    // Отображение ошибок
    if (Object.keys(errors).length > 0) {
        Object.keys(errors).forEach(field => {
            const errorElement = document.getElementById(field + 'Error');
            const inputElement = this.querySelector(`[name="${field}"]`);
            if (errorElement) {
                errorElement.textContent = errors[field];
                errorElement.style.display = 'block';
            }
            if (inputElement) {
                inputElement.classList.add('error');
            }
        });
        trackRSY('form_validation_failed');
        return;
    }

    // Блокировка кнопки
    submitButton.disabled = true;
    submitButton.classList.add('loading');

    try {
        // Запись попытки
        rateLimiter.recordAttempt();

        // Отправка в Telegram (безопасная)
        const telegramResult = await sendToTelegram(data);
        
        if (telegramResult.success) {
            showSuccess('Сообщение успешно отправлено! Я свяжусь с вами в течение 15 минут.');
            trackRSY('form_submission_success', {
                service: data.service,
                has_phone: !!data.phone
            });
            this.reset();
        } else {
            showError('Произошла ошибка при отправке. Пожалуйста, попробуйте ещё раз.');
            trackRSY('form_telegram_error');
        }

    } catch (error) {
        showError('Произошла ошибка при отправке. Пожалуйста, попробуйте ещё раз.');
        trackRSY('form_submission_error');
        console.error('Form submission error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.classList.remove('loading');
    }
});