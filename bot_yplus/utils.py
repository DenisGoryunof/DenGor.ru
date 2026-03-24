import json
import os
from datetime import datetime
from typing import Optional

DATA_FILE = "data.json"
MONTH_PRICE = 100  # по умолчанию

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