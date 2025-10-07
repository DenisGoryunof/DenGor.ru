// netlify/functions/telegram.js - улучшенная версия с защитой от XSS
exports.handler = async function(event, context) {
  // CORS headers
  const headers = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Content-Type',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Content-Type': 'application/json'
  };

  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 200, headers, body: '' };
  }

  if (event.httpMethod !== 'POST') {
    return { statusCode: 405, headers, body: JSON.stringify({ error: 'Method Not Allowed' }) };
  }

  try {
    const data = JSON.parse(event.body);
    console.log('Получены данные:', data);

    // Улучшенная валидация
    const validationErrors = validateFormData(data);
    if (validationErrors.length > 0) {
      console.log('Ошибки валидации:', validationErrors);
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ error: validationErrors.join(', ') })
      };
    }

    // Получаем данные из переменных окружения
    const BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN;
    const CHAT_ID = process.env.TELEGRAM_CHAT_ID;

    if (!BOT_TOKEN || !CHAT_ID) {
      return {
        statusCode: 500,
        headers,
        body: JSON.stringify({ error: 'Server configuration error' })
      };
    }

    // Безопасное форматирование сообщения
    const message = createSafeMessage(data, event);
    console.log('Безопасное сообщение создано');

    // Отправляем в Telegram
    const telegramResponse = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        chat_id: CHAT_ID,
        text: message,
        parse_mode: 'HTML'
      })
    });

    const result = await telegramResponse.json();

    if (!result.ok) {
      throw new Error(result.description || 'Telegram API error');
    }

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({ success: true, message: 'Сообщение отправлено!' })
    };

  } catch (error) {
    console.error('Ошибка:', error);
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({ error: 'Ошибка отправки: ' + error.message })
    };
  }
};

// Улучшенная валидация данных
function validateFormData(data) {
  const errors = [];
  
  // Проверка обязательных полей
  if (!data.name || data.name.trim().length < 2) {
    errors.push('Имя должно содержать минимум 2 символа');
  }
  
  if (!data.email || !isValidEmail(data.email)) {
    errors.push('Введите корректный email');
  }
  
  if (!data.message || data.message.trim().length < 10) {
    errors.push('Сообщение должно содержать минимум 10 символов');
  }
  
  // Проверка длины
  if (data.name && data.name.length > 50) errors.push('Имя слишком длинное');
  if (data.email && data.email.length > 100) errors.push('Email слишком длинный');
  if (data.message && data.message.length > 1000) errors.push('Сообщение слишком длинное');
  
  // Защита от XSS - проверка на опасные символы
  if (containsXSS(data.name) || containsXSS(data.email) || containsXSS(data.message)) {
    errors.push('Обнаружены недопустимые символы');
  }
  
  return errors;
}

// Проверка email
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Проверка на XSS-атаки
function containsXSS(text) {
  if (!text) return false;
  
  const xssPatterns = [
    /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
    /javascript:/gi,
    /on\w+\s*=/gi,
    /<iframe/gi,
    /<object/gi,
    /<embed/gi,
    /<form/gi,
    /<meta/gi
  ];
  
  return xssPatterns.some(pattern => pattern.test(text));
}

// Создание безопасного сообщения
function createSafeMessage(data, event) {
  // Экранирование HTML-символов
  const escapeHtml = (text) => {
    if (!text) return '';
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  const name = escapeHtml(data.name);
  const email = escapeHtml(data.email);
  const phone = data.phone ? escapeHtml(data.phone) : '';
  const message = escapeHtml(data.message);
  const service = getServiceName(data.service);
  
  const ip = event.headers['client-ip'] || event.headers['x-forwarded-for'] || 'Unknown';

  return `
🆕 Новая заявка с сайта!

👤 Имя: ${name}
📧 Email: ${email}
${phone ? `📞 Телефон: ${phone}` : ''}
🎯 Услуга: ${service}
💬 Сообщение:
${message}

📅 Дата: ${new Date().toLocaleString('ru-RU')}
🌐 IP: ${ip}
  `.trim();
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