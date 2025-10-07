// js/protection.js - улучшенная версия
async function sendToTelegram(formData) {
  console.log('📤 Отправка данных в функцию:', formData);
  
  // Дополнительная проверка данных
  if (!formData.name || !formData.email || !formData.message) {
    return { success: false, error: 'Недостаточно данных' };
  }

  // Проверка длины данных
  if (formData.message.length > 1000) {
    return { success: false, error: 'Сообщение слишком длинное' };
  }

  try {
    const response = await fetch('/.netlify/functions/telegram', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    });

    console.log('📨 Статус ответа:', response.status);
    
    // Таймаут 10 секунд
    const timeoutPromise = new Promise((_, reject) => 
      setTimeout(() => reject(new Error('Timeout')), 10000)
    );
    
    const result = await Promise.race([response.json(), timeoutPromise]);
    console.log('📩 Результат:', result);
    
    if (response.ok) {
      return { success: true, message: result.message };
    } else {
      return { success: false, error: result.error || 'Ошибка сервера' };
    }
    
  } catch (error) {
    console.error('❌ Ошибка сети:', error);
    return { 
      success: false, 
      error: 'Ошибка соединения. Попробуйте позже.' 
    };
  }
}

window.sendToTelegram = sendToTelegram;