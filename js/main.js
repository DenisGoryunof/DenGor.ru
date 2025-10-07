// В блоке try после валидации:
try {
    // Запись попытки
    rateLimiter.recordAttempt();

    // Отправка через Netlify Function
    const telegramResult = await sendToTelegram(data);
    
    if (telegramResult.success) {
        showSuccess(telegramResult.message || 'Сообщение отправлено успешно!');
        document.getElementById('protectedForm').reset();
        
        // Трекинг успешной отправки
        if (typeof trackRSY === 'function') {
            trackRSY('form_submission_success');
        }
    } else {
        throw new Error(telegramResult.error);
    }

} catch (error) {
    showError('Произошла ошибка при отправке. Пожалуйста, попробуйте ещё раз.');
    trackRSY('form_submission_error');
    console.error('Form submission error:', error);
} finally {
    submitButton.disabled = false;
    submitButton.classList.remove('loading');
}