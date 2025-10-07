// netlify/functions/telegram.js
const fetch = require('node-fetch');

exports.handler = async function(event, context) {
    // Проверяем метод запроса
    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405,
            body: JSON.stringify({ error: 'Method Not Allowed' })
        };
    }

    try {
        const data = JSON.parse(event.body);
        
        // Валидация данных
        if (!data.name || !data.email || !data.message) {
            return {
                statusCode: 400,
                body: JSON.stringify({ error: 'Missing required fields' })
            };
        }

        // Ваши реальные данные (замените на свои)
        const BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN;
        const CHAT_ID = process.env.TELEGRAM_CHAT_ID;

        if (!BOT_TOKEN || !CHAT_ID) {
            return {
                statusCode: 500,
                body: JSON.stringify({ error: 'Server configuration error' })
            };
        }

        // Форматируем сообщение для Telegram
        const message = `
🆕 Новая заявка с сайта!

👤 Имя: ${escapeHtml(data.name)}
📧 Email: ${escapeHtml(data.email)}
${data.phone ? `📞 Телефон: ${escapeHtml(data.phone)}` : ''}
🎯 Услуга: ${getServiceName(data.service)}
💬 Сообщение:
${escapeHtml(data.message)}

📅 Дата: ${new Date().toLocaleString('ru-RU')}
🌐 IP: ${event.headers['client-ip'] || 'Unknown'}
        `.trim();

        // Отправляем в Telegram
        const telegramUrl = `https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`;
        
        const response = await fetch(telegramUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: CHAT_ID,
                text: message,
                parse_mode: 'HTML'
            })
        });

        const result = await response.json();

        if (!result.ok) {
            throw new Error(result.description || 'Telegram API error');
        }

        return {
            statusCode: 200,
            body: JSON.stringify({ 
                success: true, 
                message: 'Сообщение отправлено успешно!' 
            })
        };

    } catch (error) {
        console.error('Telegram function error:', error);
        return {
            statusCode: 500,
            body: JSON.stringify({ 
                error: 'Произошла ошибка при отправке сообщения' 
            })
        };
    }
};

// Вспомогательные функции
function escapeHtml(text) {
    if (!text) return '';
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