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
        // Парсим данные из тела запроса
        const { name, email, message } = JSON.parse(event.body);
        
        // Валидация данных
        if (!name || !email || !message) {
            return {
                statusCode: 400,
                body: JSON.stringify({ error: 'Все поля обязательны для заполнения' })
            };
        }

        // Получаем секретные данные из environment variables
        const botToken = process.env.TELEGRAM_BOT_TOKEN;
        const chatId = process.env.TELEGRAM_CHAT_ID;

        if (!botToken || !chatId) {
            console.error('Telegram credentials not configured');
            return {
                statusCode: 500,
                body: JSON.stringify({ error: 'Server configuration error' })
            };
        }

        // Формируем сообщение для Telegram
        const text = `📧 Новое сообщение с сайта:\n\n👤 Имя: ${name}\n📧 Email: ${email}\n💬 Сообщение: ${message}\n\n🌐 Сайт: ${event.headers.origin}`;

        // Отправляем в Telegram
        const response = await fetch(`https://api.telegram.org/bot${botToken}/sendMessage`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: chatId,
                text: text,
                parse_mode: 'Markdown'
            })
        });

        const result = await response.json();

        if (!response.ok) {
            console.error('Telegram API error:', result);
            return {
                statusCode: 500,
                body: JSON.stringify({ error: 'Failed to send message to Telegram' })
            };
        }

        // Возвращаем успешный ответ
        return {
            statusCode: 200,
            body: JSON.stringify({ 
                success: true, 
                message: 'Message sent successfully' 
            })
        };

    } catch (error) {
        console.error('Error in send-to-telegram function:', error);
        
        return {
            statusCode: 500,
            body: JSON.stringify({ 
                error: 'Internal server error',
                message: error.message 
            })
        };
    }
};