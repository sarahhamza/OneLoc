# test.py  ← run this once
from login import login_user

success, uuid = login_user()

print("=== RAW RETURN VALUES ===")
print("success :", success, type(success))
print("uuid    :", uuid, type(uuid))
print("uuid is None?", uuid is None)
print("uuid length :", len(str(uuid)) if uuid else 0)