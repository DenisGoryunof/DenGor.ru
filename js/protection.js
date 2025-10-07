// Простая отправка формы
async function sendToTelegram(formData) {
  console.log('📤 Отправка данных в функцию:', formData);
  
  try {
    const response = await fetch('/.netlify/functions/telegram', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    });

    console.log('📨 Статус ответа:', response.status);
    
    const result = await response.json();
    console.log('📩 Результат:', result);
    
    if (response.ok) {
      return { success: true, message: result.message };
    } else {
      return { success: false, error: result.error };
    }
    
  } catch (error) {
    console.error('❌ Ошибка сети:', error);
    return { 
      success: false, 
      error: 'Ошибка сети: ' + error.message 
    };
  }
}

window.sendToTelegram = sendToTelegram;