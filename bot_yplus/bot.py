import asyncio
import json
import os
from datetime import datetime, timedelta
from typing import Dict, Optional

from aiogram import Bot, Dispatcher, types
from aiogram.filters import Command
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton, CallbackQuery, Message
from aiogram.utils.keyboard import InlineKeyboardBuilder
from aiogram.webhook.aiohttp_server import SimpleRequestHandler, setup_application
from aiohttp import web

# ========== КОНФИГУРАЦИЯ ==========
BOT_TOKEN = os.getenv("BOT_TOKEN")  # Получить из переменных окружения
ADMIN_ID = int(os.getenv("ADMIN_ID"))  # Ваш Telegram ID
GROUP_ID = int(os.getenv("GROUP_ID"))  # ID группы, где работает бот

DATA_FILE = "data.json"

# Цена за месяц (по умолчанию)
MONTH_PRICE = 100

# ========== РАБОТА С JSON ==========
def load_data() -> dict:
    if not os.path.exists(DATA_FILE):
        return {"users": {}, "settings": {"price_per_month": MONTH_PRICE}}
    with open(DATA_FILE, "r", encoding="utf-8") as f:
        return json.load(f)

def save_data(data: dict):
    with open(DATA_FILE, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

def get_user(user_id: int) -> Optional[dict]:
    data = load_data()
    return data["users"].get(str(user_id))

def update_user(user_id: int, **kwargs):
    data = load_data()
    user_id_str = str(user_id)
    if user_id_str not in data["users"]:
        data["users"][user_id_str] = {
            "subscription_end": "1970-01-01",
            "username": None,
            "first_name": None
        }
    user = data["users"][user_id_str]
    for key, value in kwargs.items():
        if value is not None:
            user[key] = value
    save_data(data)

def set_subscription_end(user_id: int, end_date: datetime):
    update_user(user_id, subscription_end=end_date.strftime("%Y-%m-%d"))

def get_subscription_end(user_id: int) -> Optional[datetime]:
    user = get_user(user_id)
    if user:
        end_str = user.get("subscription_end")
        if end_str and end_str != "1970-01-01":
            return datetime.strptime(end_str, "%Y-%m-%d")
    return None

def is_subscription_active(user_id: int) -> bool:
    end = get_subscription_end(user_id)
    return end and end >= datetime.now().date()

# ========== КЛАВИАТУРЫ ==========
def main_menu_keyboard():
    builder = InlineKeyboardBuilder()
    builder.button(text="💰 Оплатить", callback_data="pay")
    builder.button(text="📅 Моя подписка", callback_data="my_subscription")
    builder.adjust(1)
    return builder.as_markup()

def payment_amount_keyboard():
    builder = InlineKeyboardBuilder()
    for amount in [100, 200, 300, 500]:
        builder.button(text=f"{amount} руб", callback_data=f"amount_{amount}")
    builder.button(text="🔢 Другая сумма", callback_data="amount_custom")
    builder.button(text="◀️ Назад", callback_data="back_to_main")
    builder.adjust(2)
    return builder.as_markup()

def admin_confirm_keyboard(user_id: int, amount: int):
    builder = InlineKeyboardBuilder()
    builder.button(text="✅ Подтвердить", callback_data=f"confirm_{user_id}_{amount}")
    builder.button(text="❌ Отклонить", callback_data=f"reject_{user_id}_{amount}")
    builder.adjust(1)
    return builder.as_markup()

def admin_panel_keyboard():
    builder = InlineKeyboardBuilder()
    builder.button(text="💰 Установить цену", callback_data="admin_set_price")
    builder.button(text="📋 Список подписчиков", callback_data="admin_list_users")
    builder.button(text="🔧 Продлить вручную", callback_data="admin_manual_extend")
    builder.button(text="◀️ Назад", callback_data="back_to_main")
    builder.adjust(1)
    return builder.as_markup()

# ========== ОБРАБОТЧИКИ ==========
async def send_admin_notification(bot: Bot, user_id: int, amount: int):
    user = get_user(user_id)
    username = user.get("username", "Unknown")
    text = f"🔔 Пользователь @{username} (ID: {user_id}) запросил оплату на сумму {amount} руб.\nПодтвердить продление?"
    await bot.send_message(ADMIN_ID, text, reply_markup=admin_confirm_keyboard(user_id, amount))

async def extend_subscription(bot: Bot, user_id: int, amount: int):
    data = load_data()
    price_per_month = data["settings"]["price_per_month"]
    months = amount // price_per_month
    if months == 0:
        months = 1  # минимально на месяц
    current_end = get_subscription_end(user_id)
    if current_end and current_end >= datetime.now().date():
        new_end = current_end + timedelta(days=30 * months)
    else:
        new_end = datetime.now().date() + timedelta(days=30 * months)
    set_subscription_end(user_id, datetime(new_end.year, new_end.month, new_end.day))
    await bot.send_message(user_id, f"✅ Ваша подписка продлена до {new_end.strftime('%d.%m.%Y')}.")
    # Уведомить админа
    await bot.send_message(ADMIN_ID, f"✅ Подписка пользователя {user_id} продлена на {months} мес.")

# ========== ХЭНДЛЕРЫ ==========
async def start_handler(message: Message, bot: Bot):
    user_id = message.from_user.id
    if message.chat.type == "private":
        # Добавляем или обновляем пользователя
        update_user(user_id, username=message.from_user.username, first_name=message.from_user.first_name)
        await message.answer("Добро пожаловать! Используйте меню ниже.", reply_markup=main_menu_keyboard())
    else:
        # В группе реагируем только на команды /start для бота (не нужно)
        pass

async def new_chat_member_handler(message: Message, bot: Bot):
    # Когда кто-то заходит в группу, добавляем его
    if message.chat.id == GROUP_ID:
        for member in message.new_chat_members:
            if member.id != bot.id:
                update_user(member.id, username=member.username, first_name=member.first_name)
                await bot.send_message(member.id, "Вы были добавлены в группу подписчиков. Настройте подписку, нажав /start в личке со мной.")

async def my_subscription_callback(callback: CallbackQuery):
    user_id = callback.from_user.id
    end_date = get_subscription_end(user_id)
    if end_date and end_date >= datetime.now().date():
        text = f"📅 Ваша подписка активна до {end_date.strftime('%d.%m.%Y')}."
    else:
        text = "❗ У вас нет активной подписки. Нажмите «Оплатить», чтобы продлить."
    await callback.message.edit_text(text, reply_markup=main_menu_keyboard())
    await callback.answer()

async def pay_callback(callback: CallbackQuery):
    await callback.message.edit_text("Выберите сумму оплаты:", reply_markup=payment_amount_keyboard())
    await callback.answer()

async def amount_callback(callback: CallbackQuery, bot: Bot):
    data = callback.data
    if data.startswith("amount_"):
        amount_str = data.split("_")[1]
        if amount_str == "custom":
            await callback.message.edit_text("Введите сумму в рублях (целое число):")
            # Мы будем ловить следующее сообщение от этого пользователя
            # Установим состояние ожидания ввода суммы
            # Упростим: попросим отправить число в ответ на это сообщение
            # Для простоты будем ловить следующее сообщение через отдельный хэндлер
            # В реальном боте лучше использовать FSM (aiogram.fsm)
            # Сделаем временное решение: сохраним в глобальный словарь ожидающих
            global waiting_for_custom_amount
            waiting_for_custom_amount[callback.from_user.id] = True
            await callback.answer()
            return
        else:
            amount = int(amount_str)
            await send_admin_notification(bot, callback.from_user.id, amount)
            await callback.message.edit_text(f"Запрос на оплату {amount} руб. отправлен администратору. Ожидайте подтверждения.")
            await callback.answer()
    elif data == "back_to_main":
        await callback.message.edit_text("Главное меню:", reply_markup=main_menu_keyboard())
        await callback.answer()

async def admin_callback(callback: CallbackQuery, bot: Bot):
    if callback.from_user.id != ADMIN_ID:
        await callback.answer("У вас нет прав.", show_alert=True)
        return
    if callback.data == "admin_set_price":
        await callback.message.edit_text("Введите новую цену за месяц (в рублях):")
        global waiting_for_price
        waiting_for_price = True
        await callback.answer()
    elif callback.data == "admin_list_users":
        data = load_data()
        users = data["users"]
        if not users:
            text = "Список подписчиков пуст."
        else:
            lines = []
            for uid, info in users.items():
                end = info.get("subscription_end", "неактивна")
                if end != "1970-01-01":
                    end = datetime.strptime(end, "%Y-%m-%d").strftime("%d.%m.%Y")
                else:
                    end = "неактивна"
                username = info.get("username", uid)
                lines.append(f"@{username}: {end}")
            text = "Подписчики:\n" + "\n".join(lines)
        await callback.message.edit_text(text, reply_markup=admin_panel_keyboard())
        await callback.answer()
    elif callback.data == "admin_manual_extend":
        await callback.message.edit_text("Введите ID пользователя и количество месяцев через пробел (например: 123456789 3):")
        global waiting_for_manual
        waiting_for_manual = True
        await callback.answer()
    elif callback.data == "back_to_main":
        await callback.message.edit_text("Главное меню:", reply_markup=main_menu_keyboard())
        await callback.answer()

async def confirm_callback(callback: CallbackQuery, bot: Bot):
    if callback.from_user.id != ADMIN_ID:
        await callback.answer("У вас нет прав.", show_alert=True)
        return
    data = callback.data
    if data.startswith("confirm_"):
        _, user_id_str, amount_str = data.split("_")
        user_id = int(user_id_str)
        amount = int(amount_str)
        await extend_subscription(bot, user_id, amount)
        await callback.message.edit_text(f"✅ Подтверждена оплата {amount} руб. для пользователя {user_id}. Подписка продлена.")
        await callback.answer()
    elif data.startswith("reject_"):
        _, user_id_str, amount_str = data.split("_")
        user_id = int(user_id_str)
        amount = int(amount_str)
        await bot.send_message(user_id, f"❌ Ваш запрос на оплату {amount} руб. отклонён администратором. Свяжитесь с администратором для уточнения.")
        await callback.message.edit_text(f"❌ Запрос пользователя {user_id} отклонён.")
        await callback.answer()

# ========== ОБРАБОТКА ВВОДА ТЕКСТА (custom sum, set price, manual extend) ==========
waiting_for_custom_amount = {}
waiting_for_price = False
waiting_for_manual = False

async def text_message_handler(message: Message, bot: Bot):
    global waiting_for_price, waiting_for_manual
    user_id = message.from_user.id
    # Если пользователь ожидает ввода суммы (custom amount)
    if waiting_for_custom_amount.get(user_id):
        try:
            amount = int(message.text.strip())
            if amount <= 0:
                raise ValueError
            await send_admin_notification(bot, user_id, amount)
            await message.answer(f"Запрос на оплату {amount} руб. отправлен администратору. Ожидайте подтверждения.")
        except:
            await message.answer("Пожалуйста, введите целое положительное число.")
        waiting_for_custom_amount[user_id] = False
        return

    # Если админ устанавливает цену
    if user_id == ADMIN_ID and waiting_for_price:
        try:
            new_price = int(message.text.strip())
            if new_price <= 0:
                raise ValueError
            data = load_data()
            data["settings"]["price_per_month"] = new_price
            save_data(data)
            await message.answer(f"✅ Цена за месяц установлена: {new_price} руб.")
        except:
            await message.answer("Ошибка: введите целое положительное число.")
        waiting_for_price = False
        return

    # Если админ вручную продлевает
    if user_id == ADMIN_ID and waiting_for_manual:
        try:
            parts = message.text.strip().split()
            if len(parts) != 2:
                raise ValueError
            target_id = int(parts[0])
            months = int(parts[1])
            if months <= 0:
                raise ValueError
            current_end = get_subscription_end(target_id)
            if current_end and current_end >= datetime.now().date():
                new_end = current_end + timedelta(days=30 * months)
            else:
                new_end = datetime.now().date() + timedelta(days=30 * months)
            set_subscription_end(target_id, datetime(new_end.year, new_end.month, new_end.day))
            await message.answer(f"✅ Подписка пользователя {target_id} продлена до {new_end.strftime('%d.%m.%Y')}.")
            await bot.send_message(target_id, f"Администратор продлил вашу подписку до {new_end.strftime('%d.%m.%Y')}.")
        except:
            await message.answer("Ошибка: используйте формат: ID_пользователя Количество_месяцев (например: 123456789 3)")
        waiting_for_manual = False
        return

    # Любое другое текстовое сообщение в приватном чате
    if message.chat.type == "private":
        await message.answer("Используйте меню ниже.", reply_markup=main_menu_keyboard())

# ========== ВЕБХУК И ЗАПУСК ==========
async def on_startup(bot: Bot):
    # Устанавливаем вебхук
    webhook_url = f"https://{os.getenv('PYTHONANYWHERE_DOMAIN')}/webhook"  # для PythonAnywhere
    await bot.set_webhook(webhook_url)

def main():
    bot = Bot(token=BOT_TOKEN)
    dp = Dispatcher()

    # Регистрируем хэндлеры
    dp.message.register(start_handler, Command("start"))
    dp.message.register(new_chat_member_handler, lambda msg: msg.new_chat_members is not None)
    dp.message.register(text_message_handler)

    dp.callback_query.register(my_subscription_callback, lambda c: c.data == "my_subscription")
    dp.callback_query.register(pay_callback, lambda c: c.data == "pay")
    dp.callback_query.register(amount_callback, lambda c: c.data.startswith("amount_") or c.data == "back_to_main")
    dp.callback_query.register(admin_callback, lambda c: c.data.startswith("admin_") or c.data == "back_to_main")
    dp.callback_query.register(confirm_callback, lambda c: c.data.startswith("confirm_") or c.data.startswith("reject_"))

    # Настройка веб-сервера aiohttp
    app = web.Application()
    webhook_requests_handler = SimpleRequestHandler(dispatcher=dp, bot=bot)
    webhook_requests_handler.register(app, path="/webhook")
    setup_application(app, dp, bot=bot)

    # Запуск
    web.run_app(app, host="0.0.0.0", port=int(os.getenv("PORT", 8000)))

if __name__ == "__main__":
    main()