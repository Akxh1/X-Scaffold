"""
X-Scaffold ML Quick Start Script
==================================
Run this script to set up the complete ML pipeline:
1. Generate dataset (from 81 teacher-labelled real records)
2. Train XGBoost Classifier (teacher-labelled target variable)
3. Verify model works

Usage:
    python setup_ml.py

Author: X-Scaffold Research Team
Date: March 2026 (Updated — teacher-labelled pipeline)
"""

import os
import sys

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
os.chdir(SCRIPT_DIR)

print("=" * 60)
print("X-SCAFFOLD ML PIPELINE SETUP (Teacher-Labelled)")
print("=" * 60)

# Step 1: Generate Dataset
print("\n[STEP 1/3] Generating Dataset (Teacher-Labelled)...")
print("-" * 40)
from generate_dataset import generate_dataset
df = generate_dataset(n_students=2000, output_path="xscaffold_student_dataset.csv", use_teacher_labels=True)

# Step 2: Train Model
print("\n[STEP 2/3] Training XGBoost Classifier...")
print("-" * 40)
from train_model import main as train_main
model, scaler, metrics = train_main()

# Step 3: Verify Prediction
print("\n[STEP 3/3] Verifying Prediction...")
print("-" * 40)
from predict import predict_single, load_model

# Test prediction with sample data
test_student = {
    'score_percentage': 65.0,
    'hard_question_accuracy': 55.0,
    'hint_usage_percentage': 30.0,
    'avg_confidence': 3.2,
    'answer_changes_rate': 0.5,
    'tab_switches_rate': 1.5,
    'avg_time_per_question': 70.0,
    'review_percentage': 25.0,
    'avg_first_action_latency': 6.0,
    'clicks_per_question': 6.0,
    'performance_trend': 0.0
}

result = predict_single(test_student, model, scaler)

print("\nTest Prediction Result:")
print(f"  Input: Moderate student (score=65%, hints=30%)")
print(f"  Predicted: {result['mastery_level_name'].upper()}")
print(f"  Confidence: {result['confidence']*100:.1f}%")

# Final summary
print("\n" + "=" * 60)
print("SETUP COMPLETE!")
print("=" * 60)
print("\nFiles created:")
print("  ✅ xscaffold_student_dataset.csv (2000 students, teacher-labelled)")
print("  ✅ xscaffold_xgboost_model.pkl")
print("  ✅ xscaffold_scaler.pkl")
print("  ✅ feature_names.json")
print("  ✅ model_metrics.json")
print("  ✅ feature_importance.json")
print("  ✅ model_config.json")
print(f"\nModel Performance:")
print(f"  Test Accuracy:    {metrics['test_accuracy']:.4f}")
print(f"  F1 (Weighted):    {metrics['f1_weighted']:.4f}")
print(f"  CV Accuracy:      {metrics['cv_mean']:.4f}")
if metrics.get('auc_roc_weighted'):
    print(f"  AUC-ROC:          {metrics['auc_roc_weighted']:.4f}")
print(f"  Target Source:    teacher_labelled")
print("\nNext steps:")
print("  1. Start API:  python api.py")
print("  2. Test:       curl http://localhost:5500/health")
print("=" * 60)
