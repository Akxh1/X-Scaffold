"""
X-Scaffold Prediction API
==========================
Flask API for serving ML predictions with SHAP explanations.

Endpoints:
- POST /predict - Get mastery level prediction + SHAP values
- GET /health - Health check

Author: X-Scaffold Research Team
Date: January 2026
"""

import os
import json
import numpy as np
import joblib
from flask import Flask, request, jsonify
from flask_cors import CORS

# SHAP for explainability
import shap

app = Flask(__name__)
CORS(app)  # Enable CORS for Laravel to call this API

# ============================================================
# LOAD MODEL ARTIFACTS
# ============================================================

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

# Load trained model (XGBoost Classifier)
MODEL_PATH = os.path.join(SCRIPT_DIR, 'xscaffold_xgboost_model.pkl')
model = joblib.load(MODEL_PATH)

# Load scaler
SCALER_PATH = os.path.join(SCRIPT_DIR, 'xscaffold_scaler.pkl')
scaler = joblib.load(SCALER_PATH)

# Load feature names
FEATURES_PATH = os.path.join(SCRIPT_DIR, 'feature_names.json')
with open(FEATURES_PATH, 'r') as f:
    config = json.load(f)
    FEATURE_NAMES = config['features']
    CLASS_NAMES = config['class_names']

# SHAP explainer — lazily initialized on first prediction request
# (XGBoost 3.2 + SHAP TreeExplainer causes a threading deadlock at startup)
explainer = None
SHAP_AVAILABLE = True  # Assume available, will be set to False on first-use failure

print(f"✅ Model loaded: {os.path.basename(MODEL_PATH)}")
print(f"✅ Features: {len(FEATURE_NAMES)}")
print(f"✅ Classes: {CLASS_NAMES}")

# ============================================================
# HELPER FUNCTIONS
# ============================================================

def format_shap_explanation(shap_values: np.ndarray, feature_names: list, prediction: int) -> dict:
    """
    Format SHAP values into human-readable explanation.
    
    Returns:
        dict with feature contributions and natural language summary
    """
    
    # Get SHAP values for the predicted class
    class_shap_values = shap_values[0, :, prediction]
    
    # Create feature contribution dict
    contributions = {}
    for i, name in enumerate(feature_names):
        contributions[name] = {
            'value': float(class_shap_values[i]),
            'direction': 'positive' if class_shap_values[i] > 0 else 'negative',
            'magnitude': abs(float(class_shap_values[i]))
        }
    
    # Sort by absolute magnitude
    sorted_features = sorted(
        contributions.items(), 
        key=lambda x: x[1]['magnitude'], 
        reverse=True
    )
    
    # Get top 3 positive and negative contributors
    positive_contributors = [
        f for f, v in sorted_features if v['direction'] == 'positive'
    ][:3]
    
    negative_contributors = [
        f for f, v in sorted_features if v['direction'] == 'negative'
    ][:3]
    
    # Generate natural language summary
    summary_parts = []
    
    if positive_contributors:
        pos_text = ", ".join([f"{f} (+{contributions[f]['value']:.3f})" for f in positive_contributors])
        summary_parts.append(f"Positive factors: {pos_text}")
    
    if negative_contributors:
        neg_text = ", ".join([f"{f} ({contributions[f]['value']:.3f})" for f in negative_contributors])
        summary_parts.append(f"Areas for improvement: {neg_text}")
    
    natural_language = ". ".join(summary_parts) + "."
    
    return {
        'contributions': contributions,
        'top_positive': positive_contributors,
        'top_negative': negative_contributors,
        'natural_language': natural_language
    }


def validate_features(data: dict) -> tuple:
    """Validate and extract features from request data."""
    
    missing = [f for f in FEATURE_NAMES if f not in data]
    if missing:
        return None, f"Missing features: {missing}"
    
    try:
        features = [float(data[f]) for f in FEATURE_NAMES]
        return features, None
    except (ValueError, TypeError) as e:
        return None, f"Invalid feature values: {e}"


# ============================================================
# API ENDPOINTS
# ============================================================

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint."""
    return jsonify({
        'status': 'healthy',
        'model': 'xscaffold_xgboost_model',
        'features_count': len(FEATURE_NAMES),
        'classes': CLASS_NAMES,
        'shap_available': SHAP_AVAILABLE
    })


@app.route('/predict', methods=['POST'])
def predict():
    """
    Predict mastery level with SHAP explanation.
    
    Request body (JSON):
    {
        "score_percentage": 72.5,
        "hard_question_accuracy": 65.0,
        "hint_usage_percentage": 20.0,
        "avg_confidence": 3.8,
        "answer_changes_rate": 0.3,
        "tab_switches_rate": 0.8,
        "avg_time_per_question": 85.0,
        "review_percentage": 40.0,
        "avg_first_action_latency": 4.5,
        "clicks_per_question": 4.2,
        "performance_trend": 8.0
    }
    
    Response:
    {
        "prediction": {
            "mastery_level": 2,
            "mastery_level_name": "proficient",
            "confidence": 0.85,
            "probabilities": {...}
        },
        "explanation": {
            "contributions": {...},
            "top_positive": [...],
            "top_negative": [...],
            "natural_language": "..."
        }
    }
    """
    
    # Parse request
    data = request.get_json()
    if not data:
        return jsonify({'error': 'No JSON data provided'}), 400
    
    # Validate features
    features, error = validate_features(data)
    if error:
        return jsonify({'error': error}), 400
    
    # Scale features
    features_array = np.array(features).reshape(1, -1)
    features_scaled = scaler.transform(features_array)
    
    # Get prediction
    prediction = int(model.predict(features_scaled)[0])
    probabilities = model.predict_proba(features_scaled)[0]
    confidence = float(probabilities[prediction])
    
    response = {
        'prediction': {
            'mastery_level': prediction,
            'mastery_level_name': CLASS_NAMES[prediction],
            'confidence': round(confidence, 4),
            'probabilities': {
                CLASS_NAMES[i]: round(float(p), 4) 
                for i, p in enumerate(probabilities)
            }
        }
    }
    
    # Add SHAP explanation if available
    global explainer, SHAP_AVAILABLE
    if SHAP_AVAILABLE:
        try:
            if explainer is None:
                print("Lazily initializing SHAP explainer...")
                import shap
                explainer = shap.TreeExplainer(model)
                
            shap_values = explainer.shap_values(features_scaled)
            explanation = format_shap_explanation(
                shap_values, FEATURE_NAMES, prediction
            )
            response['explanation'] = explanation
        except Exception as e:
            print(f"SHAP explicitly failed during request: {e}")
            SHAP_AVAILABLE = False
            response['explanation'] = {
                'error': f'SHAP computation failed: {str(e)}',
                'natural_language': 'Explanation unavailable for this prediction.'
            }
    else:
        # Fallback: use feature importance from model
        response['explanation'] = {
            'natural_language': 'SHAP explanations not available. Use feature importance instead.',
            'note': 'Install shap package for detailed explanations.'
        }
    
    return jsonify(response)


@app.route('/batch_predict', methods=['POST'])
def batch_predict():
    """
    Batch prediction for multiple students.
    
    Request body (JSON):
    {
        "students": [
            {"score_percentage": 72.5, ...},
            {"score_percentage": 45.0, ...}
        ]
    }
    """
    
    data = request.get_json()
    if not data or 'students' not in data:
        return jsonify({'error': 'No students data provided'}), 400
    
    students = data['students']
    results = []
    
    for i, student in enumerate(students):
        features, error = validate_features(student)
        if error:
            results.append({'index': i, 'error': error})
            continue
        
        features_array = np.array(features).reshape(1, -1)
        features_scaled = scaler.transform(features_array)
        
        prediction = int(model.predict(features_scaled)[0])
        probabilities = model.predict_proba(features_scaled)[0]
        
        results.append({
            'index': i,
            'student_id': student.get('student_id', f'student_{i}'),
            'mastery_level': prediction,
            'mastery_level_name': CLASS_NAMES[prediction],
            'confidence': round(float(probabilities[prediction]), 4)
        })
    
    return jsonify({'results': results})


# ============================================================
# MAIN
# ============================================================

if __name__ == '__main__':
    print("\n" + "=" * 50)
    print("X-SCAFFOLD PREDICTION API")
    print("=" * 50)
    print(f"Starting server on http://localhost:5500")
    print("Endpoints:")
    print("  GET  /health  - Health check")
    print("  POST /predict - Single prediction with SHAP")
    print("  POST /batch_predict - Batch predictions")
    print("=" * 50 + "\n")
    
    app.run(host='0.0.0.0', port=5500, debug=False)
