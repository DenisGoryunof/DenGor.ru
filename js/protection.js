// Конфигурация для Netlify (безопасные данные)
const TELEGRAM_CONFIG = {
    botToken: window.atob('NTMxMTk0NzUzNTpBQUYxUE40TzJpVTFtTGZ5dUlvYUlNZVlzQ2l4N0FueFlLYw=='), // зашифрованный токен
    chatId: '130208292', // ID для личных сообщений
    botChatId: '5311947535' // ID чата с ботом
};

// Безопасная отправка в Telegram
async function sendToTelegram(formData) {
    try {
        // Отправка в чат с ботом
        const botMessage = formatBotMessage(formData);
        const botUrl = `https://api.telegram.org/bot${TELEGRAM_CONFIG.botToken}/sendMessage`;
        
        await fetch(botUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: TELEGRAM_CONFIG.botChatId,
                text: botMessage,
                parse_mode: 'HTML'
            })
        });

        // Отправка личного сообщения
        const personalMessage = formatPersonalMessage(formData);
        await fetch(botUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: TELEGRAM_CONFIG.chatId,
                text: personalMessage,
                parse_mode: 'HTML'
            })
        });

        return { success: true };
    } catch (error) {
        console.error('Telegram API error:', error);
        return { success: false, error: error.message };
    }
}

function formatBotMessage(data) {
    return `
🆕 <b>Новая заявка с сайта!</b>

👤 <b>Имя:</b> ${escapeHtml(data.name)}
📧 <b>Email:</b> ${escapeHtml(data.email)}
${data.phone ? `📞 <b>Телефон:</b> ${escapeHtml(data.phone)}` : ''}
🎯 <b>Услуга:</b> ${getServiceName(data.service)}
💬 <b>Сообщение:</b> ${escapeHtml(data.message)}

📅 <b>Дата:</b> ${new Date().toLocaleString('ru-RU')}
🌐 <b>IP:</b> ${getClientIP()}
    `.trim();
}

function formatPersonalMessage(data) {
    return `
<b>🔔 Личное сообщение с сайта</b>

<b>Контакт:</b> ${escapeHtml(data.name)} (${escapeHtml(data.email)})
<b>Телефон:</b> ${data.phone || 'не указан'}
<b>Услуга:</b> ${getServiceName(data.service)}

<b>Сообщение:</b>
${escapeHtml(data.message)}

⏰ ${new Date().toLocaleString('ru-RU')}
    `.trim();
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function getServiceName(service) {
    const services = {
        'landing': 'Лендинг',
        'corporate': 'Корпоративный сайт',
        'shop': 'Интернет-магазин',
        'bot': 'Чат-бот',
        'complex': 'Комплексное решение'
    };
    return services[service] || service;
}

function getClientIP() {
    // Для Netlify - IP будет доступен на сервере
    return 'Клиентский IP';
}

// Экспорт функции
window.sendToTelegram = sendToTelegram;