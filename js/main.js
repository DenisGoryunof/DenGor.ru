// main.js - полная версия с защитой от XSS
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

  // Защита от XSS
  containsXSS(text) {
    if (!text) return false;
    
    const xssPatterns = [
      /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
      /javascript:/gi,
      /on\w+\s*=/gi,
      /<iframe/gi,
      /<object/gi,
      /<embed/gi,
      /<form/gi,
      /<meta/gi,
      /expression\(/gi,
      /vbscript:/gi,
      /data:/gi
    ];
    
    return xssPatterns.some(pattern => pattern.test(text));
  },

  // Очистка текста от XSS
  sanitizeText(text) {
    if (!text) return '';
    return text
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#x27;')
      .replace(/\//g, '&#x2F;');
  }
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
      if (typeof trackRSY === 'function') trackRSY('form_spam_detected');
      return;
    }

    // Базовая валидация
    const errors = {};
    
    // Валидация имени
    if (!data.name || data.name.trim().length < 2) {
      errors.name = 'Имя должно содержать минимум 2 символа';
    } else if (data.name.length > 50) {
      errors.name = 'Имя слишком длинное (максимум 50 символов)';
    } else if (ValidationUtils.containsXSS(data.name)) {
      errors.name = 'Имя содержит недопустимые символы';
    }
    
    // Валидация email
    if (!data.email || !ValidationUtils.validateEmail(data.email)) {
      errors.email = 'Введите корректный email адрес';
    } else if (data.email.length > 100) {
      errors.email = 'Email слишком длинный';
    } else if (ValidationUtils.containsXSS(data.email)) {
      errors.email = 'Email содержит недопустимые символы';
    }
    
    // Валидация телефона
    if (data.phone && data.phone.trim() !== '') {
      if (!ValidationUtils.validatePhone(data.phone)) {
        errors.phone = 'Введите корректный номер телефона';
      } else if (ValidationUtils.containsXSS(data.phone)) {
        errors.phone = 'Телефон содержит недопустимые символы';
      }
    }
    
    // Валидация услуги
    if (!data.service) {
      errors.service = 'Выберите интересующую услугу';
    }
    
    // Валидация сообщения
    if (!data.message || data.message.trim().length < 10) {
      errors.message = 'Сообщение должно содержать минимум 10 символов';
    } else if (data.message.length > 1000) {
      errors.message = 'Сообщение слишком длинное (максимум 1000 символов)';
    } else if (ValidationUtils.containsXSS(data.message)) {
      errors.message = 'Сообщение содержит недопустимые символы';
    }

    // Проверка спама в сообщении
    const spamIssues = ValidationUtils.detectSpam(data.message);
    if (spamIssues.length > 0) {
      errors.message = spamIssues.join(', ');
      if (typeof trackRSY === 'function') trackRSY('form_spam_detected');
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

      // Очистка данных от XSS перед отправкой
      const sanitizedData = {
        name: ValidationUtils.sanitizeText(data.name),
        email: ValidationUtils.sanitizeText(data.email),
        phone: data.phone ? ValidationUtils.sanitizeText(data.phone) : '',
        service: data.service,
        message: ValidationUtils.sanitizeText(data.message),
        timestamp: data.timestamp
      };

      console.log('📤 Отправляем очищенные данные в Telegram...', sanitizedData);
      
      // Отправка в Telegram
      const telegramResult = await sendToTelegram(sanitizedData);
      
      if (telegramResult.success) {
        showSuccess(telegramResult.message || 'Сообщение отправлено успешно!');
        this.reset();
        
        // Сбрасываем таймстамп после успешной отправки
        const timestampField = document.getElementById('timestamp');
        if (timestampField) {
          timestampField.value = Date.now();
        }
        
        console.log('✅ Форма отправлена успешно');
        
        // Трекинг успеха
        if (typeof trackRSY === 'function') {
          trackRSY('form_submission_success', {
            service: data.service,
            has_phone: !!data.phone
          });
        }
      } else {
        throw new Error(telegramResult.error || 'Unknown error');
      }

    } catch (error) {
      console.error('❌ Ошибка отправки:', error);
      showError('Произошла ошибка при отправке. Пожалуйста, попробуйте ещё раз.');
      if (typeof trackRSY === 'function') trackRSY('form_submission_error', { error: error.message });
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

  // Добавляем обработчики для реального времени валидации
  addRealTimeValidation();
}

// Валидация в реальном времени
function addRealTimeValidation() {
  const form = document.getElementById('protectedForm');
  if (!form) return;

  const inputs = form.querySelectorAll('input[name], textarea[name], select[name]');
  
  inputs.forEach(input => {
    input.addEventListener('blur', function() {
      validateField(this);
    });
    
    input.addEventListener('input', function() {
      // Скрываем ошибку при вводе
      const errorElement = document.getElementById(this.name + 'Error');
      if (errorElement) {
        errorElement.style.display = 'none';
      }
      this.classList.remove('error');
    });
  });
}

// Валидация отдельного поля
function validateField(field) {
  const value = field.value.trim();
  const errorElement = document.getElementById(field.name + 'Error');
  
  if (!errorElement) return;

  let error = '';

  switch (field.name) {
    case 'name':
      if (!value) {
        error = 'Имя обязательно для заполнения';
      } else if (value.length < 2) {
        error = 'Имя должно содержать минимум 2 символа';
      } else if (value.length > 50) {
        error = 'Имя слишком длинное';
      } else if (ValidationUtils.containsXSS(value)) {
        error = 'Имя содержит недопустимые символы';
      }
      break;

    case 'email':
      if (!value) {
        error = 'Email обязателен для заполнения';
      } else if (!ValidationUtils.validateEmail(value)) {
        error = 'Введите корректный email';
      } else if (value.length > 100) {
        error = 'Email слишком длинный';
      } else if (ValidationUtils.containsXSS(value)) {
        error = 'Email содержит недопустимые символы';
      }
      break;

    case 'phone':
      if (value && !ValidationUtils.validatePhone(value)) {
        error = 'Введите корректный номер телефона';
      } else if (value && ValidationUtils.containsXSS(value)) {
        error = 'Телефон содержит недопустимые символы';
      }
      break;

    case 'message':
      if (!value) {
        error = 'Сообщение обязательно для заполнения';
      } else if (value.length < 10) {
        error = 'Сообщение должно содержать минимум 10 символов';
      } else if (value.length > 1000) {
        error = 'Сообщение слишком длинное';
      } else if (ValidationUtils.containsXSS(value)) {
        error = 'Сообщение содержит недопустимые символы';
      } else {
        const spamIssues = ValidationUtils.detectSpam(value);
        if (spamIssues.length > 0) {
          error = spamIssues.join(', ');
        }
      }
      break;
  }

  if (error) {
    errorElement.textContent = error;
    errorElement.style.display = 'block';
    field.classList.add('error');
  } else {
    errorElement.style.display = 'none';
    field.classList.remove('error');
    field.classList.add('success');
  }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
  console.log('✅ DOM загружен, инициализируем форму');
  initializeForm();
  
  // Проверяем доступность функций
  console.log('sendToTelegram available:', typeof sendToTelegram === 'function');
  console.log('trackRSY available:', typeof trackRSY === 'function');
});

// Глобальные утилиты для отладки
window.formUtils = {
  rateLimiter,
  ValidationUtils,
  testXSS: function(text) {
    return ValidationUtils.containsXSS(text);
  },
  resetRateLimit: function() {
    rateLimiter.setData({ attempts: 0, firstAttempt: Date.now() });
    console.log('✅ Rate limit сброшен');
  },
  getFormData: function() {
    const form = document.getElementById('protectedForm');
    if (!form) return null;
    const formData = new FormData(form);
    return Object.fromEntries(formData.entries());
  }
};