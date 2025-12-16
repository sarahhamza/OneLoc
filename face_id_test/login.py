# login.py  ← overwrite completely
import cv2
import os
import numpy as np
import pickle
from deepface import DeepFace

DB_PATH = "face_db"

def load_face_db():
    face_db = {}
    if not os.path.exists(DB_PATH):
        return face_db
    for file in os.listdir(DB_PATH):
        if file.endswith(".pkl"):
            name = file.split(".")[0]
            with open(os.path.join(DB_PATH, file), "rb") as f:
                face_db[name] = pickle.load(f)
    return face_db

def cosine_similarity(vec1, vec2):
    return np.dot(vec1, vec2) / (np.linalg.norm(vec1) * np.linalg.norm(vec2))

def match_face(embedding, face_db):
    best_match = None
    best_score = -1
    for name, stored in face_db.items():
        score = cosine_similarity(embedding, stored)
        print(f"Comparing with {name}: Similarity = {score:.4f}")
        if score > best_score:
            best_score = score
            best_match = name
    if best_score > 0.65:  # Safe threshold for Facenet
        return best_match, best_score  # ← FIXED: Return score too!
    return None, best_score

def login_user():
    face_db = load_face_db()
    if not face_db:
        return False, None

    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        return False, None

    print("Look at the camera → press 's' when ready")
    while True:
        ret, frame = cap.read()
        if not ret:
            break
        cv2.imshow("Face Login – Press 's' to verify", frame)
        key = cv2.waitKey(1) & 0xFF
        if key == ord('s'):
            temp_path = "temp_login.jpg"
            cv2.imwrite(temp_path, frame)
            cap.release()
            cv2.destroyAllWindows()
            break
        if key == ord('q'):
            cap.release()
            cv2.destroyAllWindows()
            return False, None

    try:
        result = DeepFace.represent(temp_path, model_name="Facenet", enforce_detection=False)
        os.remove(temp_path)  # clean up

        if not result:
            return False, None

        embedding = result[0]["embedding"]
        user_id, score = match_face(embedding, face_db)  # ← FIXED: Unpack score too!

        if user_id:
            print(f"SUCCESS → {user_id} (score {score:.3f})")  # ← Now score is available
            return True, user_id
        else:
            print("No match found")
            return False, None

    except Exception as e:
        print("DeepFace error:", e)
        if os.path.exists("temp_login.jpg"):
            os.remove("temp_login.jpg")
        return False, None

# For direct testing
if __name__ == "__main__":
    ok, uid = login_user()
    print("True" if ok else "False")