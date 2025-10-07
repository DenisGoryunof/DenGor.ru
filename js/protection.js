// Безопасная отправка формы через Netlify Function
async function sendToTelegram(formData) {
    try {
        const response = await fetch('/.netlify/functions/telegram', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Network error');
        }

        return { success: true, message: result.message };
    } catch (error) {
        console.error('Form submission error:', error);
        return { 
            success: false, 
            error: error.message || 'Произошла ошибка при отправке' 
        };
    }
}

// Экспорт функции
window.sendToTelegram = sendToTelegram;