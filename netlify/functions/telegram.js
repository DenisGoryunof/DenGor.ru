// netlify/functions/telegram.js
exports.handler = async function(event, context) {
  // Разрешаем CORS
  const headers = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Content-Type',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Content-Type': 'application/json'
  };

  // Обрабатываем preflight запрос
  if (event.httpMethod === 'OPTIONS') {
    return {
      statusCode: 200,
      headers,
      body: ''
    };
  }

  // Только POST запросы
  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ error: 'Method Not Allowed' })
    };
  }

  try {
    // Парсим данные формы
    const data = JSON.parse(event.body);
    console.log('Получены данные:', data);

    // Проверяем обязательные поля
    if (!data.name || !data.email || !data.message) {
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ error: 'Не хватает обязательных полей' })
      };
    }

    // Получаем данные из переменных окружения
    const BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN;
    const CHAT_ID = process.env.TELEGRAM_CHAT_ID;

    if (!BOT_TOKEN || !CHAT_ID) {
      console.error('Отсутствуют переменные окружения');
      return {
        statusCode: 500,
        headers,
        body: JSON.stringify({ error: 'Ошибка сервера: не настроены переменные' })
      };
    }

    // Формируем сообщение
    const message = `
🆕 Новая заявка с сайта!

👤 Имя: ${data.name}
📧 Email: ${data.email}
${data.phone ? `📞 Телефон: ${data.phone}` : ''}
🎯 Услуга: ${getServiceName(data.service)}
💬 Сообщение:
${data.message}

📅 ${new Date().toLocaleString('ru-RU')}
    `.trim();

    // Отправляем в Telegram
    const telegramResponse = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
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

    const result = await telegramResponse.json();

    if (!result.ok) {
      throw new Error(result.description || 'Ошибка Telegram API');
    }

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({ 
        success: true, 
        message: 'Сообщение отправлено!' 
      })
    };

  } catch (error) {
    console.error('Ошибка:', error);
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({ 
        error: 'Ошибка отправки: ' + error.message 
      })
    };
  }
};

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