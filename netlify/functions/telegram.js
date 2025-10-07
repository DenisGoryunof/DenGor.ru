// netlify/functions/telegram.js
const fetch = require('node-fetch');

exports.handler = async function(event, context) {
    console.log('=== TELEGRAM FUNCTION START ===');
    console.log('Method:', event.httpMethod);
    console.log('Headers:', event.headers);
    
    // Разрешаем CORS
    const headers = {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type',
        'Access-Control-Allow-Methods': 'POST, OPTIONS',
        'Content-Type': 'application/json'
    };

    // Handle preflight
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers,
            body: ''
        };
    }

    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405,
            headers,
            body: JSON.stringify({ error: 'Method Not Allowed' })
        };
    }

    try {
        let data;
        try {
            data = JSON.parse(event.body);
            console.log('📝 Полученные данные:', data);
        } catch (parseError) {
            console.error('❌ Ошибка парсинга JSON:', parseError);
            return {
                statusCode: 400,
                headers,
                body: JSON.stringify({ error: 'Invalid JSON' })
            };
        }
        
        // Базовая валидация
        if (!data.name || !data.email || !data.message) {
            console.error('❌ Не хватает полей:', { 
                name: !!data.name, 
                email: !!data.email, 
                message: !!data.message 
            });
            return {
                statusCode: 400,
                headers,
                body: JSON.stringify({ error: 'Missing required fields: name, email, message' })
            };
        }

        // Получаем данные из переменных окружения
        const BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN;
        const CHAT_ID = process.env.TELEGRAM_CHAT_ID;

        console.log('🔑 Проверяем переменные окружения...');
        console.log('BOT_TOKEN exists:', !!BOT_TOKEN);
        console.log('CHAT_ID exists:', !!CHAT_ID);
        console.log('CHAT_ID value:', CHAT_ID);

        if (!BOT_TOKEN || !CHAT_ID) {
            console.error('❌ Отсутствуют переменные окружения');
            return {
                statusCode: 500,
                headers,
                body: JSON.stringify({ error: 'Server configuration error: Missing environment variables' })
            };
        }

        // Форматируем сообщение
        const messageText = `
🆕 Новая заявка с сайта!

👤 Имя: ${escapeHtml(data.name)}
📧 Email: ${escapeHtml(data.email)}
${data.phone ? `📞 Телефон: ${escapeHtml(data.phone)}` : ''}
🎯 Услуга: ${getServiceName(data.service)}
💬 Сообщение:
${escapeHtml(data.message)}

📅 Дата: ${new Date().toLocaleString('ru-RU')}
🌐 IP: ${event.headers['client-ip'] || event.headers['x-forwarded-for'] || 'Unknown'}
        `.trim();

        console.log('📨 Текст сообщения:', messageText);

        // Отправляем в Telegram
        const telegramUrl = `https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`;
        
        console.log('🚀 Отправляем запрос в Telegram...');
        const response = await fetch(telegramUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: CHAT_ID,
                text: messageText,
                parse_mode: 'HTML'
            })
        });

        const result = await response.json();
        console.log('📩 Ответ от Telegram:', result);

        if (!result.ok) {
            console.error('❌ Ошибка Telegram API:', result.description);
            throw new Error(result.description || 'Telegram API error');
        }

        console.log('✅ Сообщение успешно отправлено в Telegram!');
        
        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({ 
                success: true, 
                message: 'Сообщение отправлено успешно!' 
            })
        };

    } catch (error) {
        console.error('💥 Критическая ошибка:', error);
        return {
            statusCode: 500,
            headers,
            body: JSON.stringify({ 
                error: 'Internal server error: ' + error.message 
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
    return services[service] || service || 'Не указана';
}