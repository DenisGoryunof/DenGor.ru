// main.js - исправленная версия
'use strict';

console.log('✅ main.js загружен');

// ---------- Rate-Limiter ----------
const rateLimiter = {
  maxAttempts: 3,
  windowMs: 10 * 60 * 1000,
  storageKey: 'formRateLimit',

  getData() {
    const raw = localStorage.getItem(this.storageKey);
    return raw ? JSON.parse(raw) : { attempts: 0, firstAttempt: Date.now() };
  },
  
  setData(data) {
    localStorage.setItem(this.storageKey, JSON.stringify(data));
  },
  
  canSubmit() {
    const data = this.getData();
    const now = Date.now();
    const timePassed = now - data.firstAttempt;

    if (data.attempts >= this.maxAttempts && timePassed < this.windowMs) {
      return {
        canSubmit: false,
        reason: 'rate_limit',
        timeRemaining: this.windowMs - timePassed,
      };
    }
    
    if (timePassed >= this.windowMs) {
      this.setData({ attempts: 0, firstAttempt: now });
    }
    
    return { canSubmit: true };
  },
  
  recordAttempt() {
    const data = this.getData();
    data.attempts += 1;
    if (data.attempts === 1) data.firstAttempt = Date.now();
    this.setData(data);
  },
};

// ---------- ValidationUtils ----------
const ValidationUtils = {
  validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  },
  
  validatePhone(phone) {
    return /^\+?\d{6,15}$/.test(phone.replace(/\s|-|\(|\)/g, ''));
  },
  
  validateTimeSpent(ms) {
    const issues = [];
    if (ms < 3000) issues.push('Форма заполнена слишком быстро');
    if (ms > 300000) issues.push('Форма заполнена слишком долго');
    return issues;
  },
  
  detectSpam(text) {
    const spamWords = ['viagra', 'casino', 'crypto', 'http', 'www', '.ru', '.com'];
    const found = spamWords.filter(w => text.toLowerCase().includes(w));
    return found.length ? [`Обнаружены запрещённые слова: ${found.join(', ')}`] : [];
  },
};

// Вспомогательные функции
function showError(msg) {
  const fs = document.getElementById('formStatus');
  if (fs) {
    fs.textContent = msg;
    fs.className = 'form-status error';
    fs.style.display = 'block';
  }
  if (typeof trackRSY === 'function') trackRSY('form_client_error', { message: msg });
}

function showSuccess(msg) {
  const fs = document.getElementById('formStatus');
  if (fs) {
    fs.textContent = msg;
    fs.className = 'form-status success';
    fs.style.display = 'block';
  }
  if (typeof trackRSY === 'function') trackRSY('form_submission_success');
}

// Основной обработчик формы
function initializeForm() {
  const form = document.getElementById('protectedForm');
  if (!form) {
    console.log('❌ Форма не найдена');
    return;
  }

  console.log('✅ Форма найдена, добавляем обработчик');

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    console.log('🔄 Отправка формы начата');
    
    const submitButton = document.getElementById('submitButton');
    const formStatus = document.getElementById('formStatus');
    const rateLimitInfo = document.getElementById('rateLimitInfo');
    
    // Очистка предыдущих ошибок
    document.querySelectorAll('.error-message').forEach(msg => msg.style.display = 'none');
    document.querySelectorAll('.form-input').forEach(input => {
      input.classList.remove('error', 'success');
    });
    
    if (formStatus) formStatus.style.display = 'none';
    if (rateLimitInfo) rateLimitInfo.style.display = 'none';

    // Проверка rate limiting
    const rateLimitCheck = rateLimiter.canSubmit();
    if (!rateLimitCheck.canSubmit) {
      if (rateLimitCheck.reason === 'rate_limit' && rateLimitInfo) {
        const minutes = Math.ceil(rateLimitCheck.timeRemaining / 60000);
        rateLimitInfo.innerHTML = `⏱️ Слишком много попыток. Пожалуйста, подождите ${minutes} минут.`;
        rateLimitInfo.style.display = 'block';
        if (typeof trackRSY === 'function') trackRSY('form_rate_limited');
        return;
      }
    }

    // Получение данных формы
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    console.log('📝 Данные формы:', data);

    // Валидация honeypot
    if (data.honeypot || data.email_confirm) {
      showError('Обнаружена подозрительная активность');
      return;
    }

    // Базовая валидация
    const errors = {};
    
    if (!data.name || data.name.length < 2) {
      errors.name = 'Имя должно содержать минимум 2 символа';
    }
    
    if (!data.email || !ValidationUtils.validateEmail(data.email)) {
      errors.email = 'Введите корректный email адрес';
    }
    
    if (data.phone && !ValidationUtils.validatePhone(data.phone)) {
      errors.phone = 'Введите корректный номер телефона';
    }

    if (!data.service) {
      errors.service = 'Выберите интересующую услугу';
    }
    
    if (!data.message || data.message.length < 10) {
      errors.message = 'Сообщение должно содержать минимум 10 символов';
    }
    
    if (data.message.length > 1000) {
      errors.message = 'Сообщение слишком длинное (максимум 1000 символов)';
    }

    // Отображение ошибок
    if (Object.keys(errors).length > 0) {
      Object.keys(errors).forEach(field => {
        const errorElement = document.getElementById(field + 'Error');
        const inputElement = this.querySelector(`[name="${field}"]`);
        if (errorElement) {
          errorElement.textContent = errors[field];
          errorElement.style.display = 'block';
        }
        if (inputElement) {
          inputElement.classList.add('error');
        }
      });
      if (typeof trackRSY === 'function') trackRSY('form_validation_failed');
      return;
    }

    // Блокировка кнопки
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.classList.add('loading');
    }

    try {
      // Запись попытки
      rateLimiter.recordAttempt();

      // Отправка в Telegram
      console.log('📤 Отправляем данные в Telegram...');
      const telegramResult = await sendToTelegram(data);
      
      if (telegramResult.success) {
        showSuccess(telegramResult.message || 'Сообщение отправлено успешно!');
        this.reset();
        console.log('✅ Форма отправлена успешно');
      } else {
        throw new Error(telegramResult.error || 'Unknown error');
      }

    } catch (error) {
      console.error('❌ Ошибка отправки:', error);
      showError('Произошла ошибка при отправке. Пожалуйста, попробуйте ещё раз.');
      if (typeof trackRSY === 'function') trackRSY('form_submission_error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.classList.remove('loading');
      }
    }
  });

  // Устанавливаем timestamp при загрузке
  const timestampField = document.getElementById('timestamp');
  if (timestampField) {
    timestampField.value = Date.now();
  }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
  console.log('✅ DOM загружен, инициализируем форму');
  initializeForm();
});