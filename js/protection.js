async function sendToTelegram(formData) {
    try {
        console.log('Отправляем данные:', formData);
        
        const response = await fetch('/.netlify/functions/telegram', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        return data;
        
    } catch (error) {
        console.error('Ошибка:', error);
        return { error: error.message };
    }
}

window.sendToTelegram = sendToTelegram;