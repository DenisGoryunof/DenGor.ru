import os
import asyncio
from datetime import datetime, timedelta
from bot import BOT_TOKEN, ADMIN_ID, GROUP_ID, load_data, get_user, is_subscription_active
from aiogram import Bot

async def send_reminders():
    bot = Bot(token=BOT_TOKEN)
    data = load_data()
    users = data["users"]
    today = datetime.now().date()
    tomorrow = today + timedelta(days=1)

    for uid_str, info in users.items():
        uid = int(uid_str)
        if uid == ADMIN_ID:
            continue
        end_str = info.get("subscription_end")
        if not end_str or end_str == "1970-01-01":
            # подписки нет – напоминать каждый день
            text = "❗ У вас нет активной подписки. Пожалуйста, оплатите, нажав /pay в группе."
            await bot.send_message(uid, text)
            continue
        end_date = datetime.strptime(end_str, "%Y-%m-%d").date()
        if end_date <= tomorrow:
            text = f"⚠️ Ваша подписка истекает {end_date.strftime('%d.%m.%Y')}. Для продления нажмите /pay в группе."
            if end_date < today:
                text = f"⚠️ Ваша подписка истекла {end_date.strftime('%d.%m.%Y')}. Пожалуйста, оплатите, нажав /pay в группе."
            await bot.send_message(uid, text)

    await bot.session.close()

if __name__ == "__main__":
    asyncio.run(send_reminders())