import os
import cv2
import pickle
import uuid
from deepface import DeepFace

DB_PATH = "face_db"

if not os.path.exists(DB_PATH):
    os.makedirs(DB_PATH)

def register_user():
    # Generate a unique ID for the user
    name = str(uuid.uuid4())
    print(f"Registering with name: {name}")

    cap = cv2.VideoCapture(0)
    print("Look at the camera for face capture... Press 's' to save.")

    while True:
        ret, frame = cap.read()
        if not ret:
            print("Error: Unable to access webcam.")
            return False

        cv2.imshow("Register - Press 's' to Save", frame)

        if cv2.waitKey(1) & 0xFF == ord('s'):
            cap.release()
            cv2.destroyAllWindows()
            break

    temp_img = f"{DB_PATH}/{name}.jpg"
    cv2.imwrite(temp_img, frame)

    try:
        face_data = DeepFace.represent(temp_img, model_name="Facenet")

        if face_data:
            embedding = face_data[0]["embedding"]

            with open(f"{DB_PATH}/{name}.pkl", "wb") as f:
                pickle.dump(embedding, f)

            print(f"Registration successful for: {name}")
            return name  # return ID on success

        else:
            print("No face detected! Try again.")
            return False

    except Exception as e:
        print(f"Error processing face: {e}")
        return False

if __name__ == "__main__":
    success = register_user()
    print("True" if success else "False")
