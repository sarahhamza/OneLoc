# api.py
from flask import Flask, request, jsonify
from register import register_user
from login import login_user
from flask_cors import CORS

app = Flask(__name__)
CORS(app, resources={
    r"/*": {
        "origins": ["http://localhost:8000", "http://127.0.0.1:8000"],
        "methods": ["GET", "POST", "OPTIONS"],
        "allow_headers": ["Content-Type"]
    }
})

@app.route('/register', methods=['POST'])
def register():
    user_uuid = register_user()
    if user_uuid:
        return jsonify({'status': 'success', 'uuid': user_uuid})
    return jsonify({'status': 'failed'})


@app.route('/login', methods=['POST'])
def login():
    success, user_uuid = login_user()      # ← now returns two values
    if success and user_uuid:
        return jsonify({
            'status': 'success',
            'uuid': user_uuid                  # ← THIS IS WHAT WAS MISSING
        })
    return jsonify({'status': 'failed'}), 401  
    
if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)