"""
X-Scaffold ML Model Training Script
=====================================
Trains an XGBoost Classifier for student mastery level prediction.

Model Architecture:
- Algorithm: XGBoost (Extreme Gradient Boosting)
- Task: 4-class classification (at_risk, developing, proficient, advanced)

Features (11):
- score_percentage, hard_question_accuracy, hint_usage_percentage
- avg_confidence, answer_changes_rate, tab_switches_rate
- avg_time_per_question, review_percentage, avg_first_action_latency
- clicks_per_question, performance_trend

Target:
- mastery_level: 0=at_risk, 1=developing, 2=proficient, 3=advanced
- Source: Teacher-assigned labels (primary) or formulaic LMS (fallback)
  The target variable is determined by generate_dataset.py.
  When teacher labels are used, the circular dependency on the LMS
  formula is eliminated — the model learns genuine teacher judgement.

Research Justification:
- XGBoost ranked #1 across ALL metrics in model comparison testing
- Superior at-risk class detection
- Built-in L1/L2 regularization prevents overfitting
- Natively compatible with SHAP TreeExplainer for XAI
- Industry standard for tabular/structured data classification

Date: March 2026 (teacher-labelled target variable)
"""

import numpy as np
import pandas as pd
import joblib
import json
import os
from datetime import datetime

# Scikit-learn imports
from sklearn.model_selection import train_test_split, cross_val_score, StratifiedKFold
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    classification_report, confusion_matrix, roc_auc_score
)
from sklearn.inspection import permutation_importance

# XGBoost
import xgboost as xgb

import warnings
warnings.filterwarnings('ignore')

# ============================================================
# CONFIGURATION
# ============================================================

FEATURE_COLUMNS = [
    'score_percentage',
    'hard_question_accuracy', 
    'hint_usage_percentage',
    'avg_confidence',
    'answer_changes_rate',
    'tab_switches_rate',
    'avg_time_per_question',
    'review_percentage',
    'avg_first_action_latency',
    'clicks_per_question',
    'performance_trend'
]

TARGET_COLUMN = 'mastery_level'

CLASS_NAMES = ['at_risk', 'developing', 'proficient', 'advanced']

# XGBoost hyperparameters (tuned for educational data)
XGBOOST_CONFIG = {
    'n_estimators': 50,           # Number of boosting rounds
    'max_depth': 8,               # Maximum tree depth
    'learning_rate': 0.1,         # Step size shrinkage
    'eval_metric': 'mlogloss',    # Multi-class log loss
    'use_label_encoder': False,   # Suppress deprecation warning
    'random_state': 42,
    'n_jobs': -1                  # Use all CPU cores
}

# ============================================================
# TRAINING FUNCTIONS
# ============================================================

def load_dataset(filepath: str) -> tuple:
    """Load and prepare dataset for training."""
    
    print(f"Loading dataset from: {filepath}")
    df = pd.read_csv(filepath)
    
    print(f"  Total samples: {len(df)}")
    print(f"  Features: {len(FEATURE_COLUMNS)}")
    
    X = df[FEATURE_COLUMNS].values
    y = df[TARGET_COLUMN].values
    
    # Print class distribution
    print("\n  Class distribution:")
    for i, name in enumerate(CLASS_NAMES):
        count = (y == i).sum()
        print(f"    {name}: {count} ({count/len(y)*100:.1f}%)")
    
    return X, y, df


def train_xgboost_classifier(X_train, y_train):
    """Train XGBoost Classifier."""
    
    print("\n" + "=" * 50)
    print("TRAINING XGBOOST CLASSIFIER")
    print("=" * 50)
    
    # Create XGBoost Classifier
    model = xgb.XGBClassifier(**XGBOOST_CONFIG)
    
    print("\nModel Configuration:")
    print(f"  Algorithm: XGBoost (Gradient Boosting)")
    print(f"  Number of Estimators: {XGBOOST_CONFIG['n_estimators']}")
    print(f"  Max Depth: {XGBOOST_CONFIG['max_depth']}")
    print(f"  Learning Rate: {XGBOOST_CONFIG['learning_rate']}")
    print(f"  Eval Metric: {XGBOOST_CONFIG['eval_metric']}")
    
    # Train model
    print("\nTraining...")
    start_time = datetime.now()
    model.fit(X_train, y_train)
    training_time = (datetime.now() - start_time).total_seconds()
    
    print(f"  Training completed in {training_time:.2f} seconds")
    
    return model


def evaluate_model(model, X_test, y_test, X_train, y_train):
    """Comprehensive model evaluation."""
    
    print("\n" + "=" * 50)
    print("MODEL EVALUATION")
    print("=" * 50)
    
    # Predictions
    y_pred = model.predict(X_test)
    y_pred_proba = model.predict_proba(X_test)
    
    # Training accuracy
    train_acc = accuracy_score(y_train, model.predict(X_train))
    test_acc = accuracy_score(y_test, y_pred)
    
    print(f"\n  Training Accuracy: {train_acc:.4f}")
    print(f"  Test Accuracy:     {test_acc:.4f}")
    print(f"  Overfitting Gap:   {train_acc - test_acc:.4f}")
    
    # Detailed metrics
    print("\n  Per-Class Metrics:")
    print("-" * 50)
    
    precision = precision_score(y_test, y_pred, average=None)
    recall = recall_score(y_test, y_pred, average=None)
    f1 = f1_score(y_test, y_pred, average=None)
    
    for i, name in enumerate(CLASS_NAMES):
        print(f"    {name:12s}  P: {precision[i]:.3f}  R: {recall[i]:.3f}  F1: {f1[i]:.3f}")
    
    # Overall metrics
    print("-" * 50)
    print(f"    {'Macro Avg':12s}  P: {precision.mean():.3f}  R: {recall.mean():.3f}  F1: {f1.mean():.3f}")
    print(f"    {'Weighted Avg':12s}  P: {precision_score(y_test, y_pred, average='weighted'):.3f}  "
          f"R: {recall_score(y_test, y_pred, average='weighted'):.3f}  "
          f"F1: {f1_score(y_test, y_pred, average='weighted'):.3f}")
    
    # AUC-ROC (One-vs-Rest, weighted)
    try:
        from sklearn.preprocessing import label_binarize
        y_test_bin = label_binarize(y_test, classes=[0, 1, 2, 3])
        auc_roc = roc_auc_score(y_test_bin, y_pred_proba, multi_class='ovr', average='weighted')
        print(f"\n  AUC-ROC (Weighted OVR): {auc_roc:.4f}")
    except Exception:
        auc_roc = None
    
    # Confusion Matrix
    print("\n  Confusion Matrix:")
    cm = confusion_matrix(y_test, y_pred)
    print("  " + "-" * 45)
    print(f"  {'':12s} | " + " | ".join([f"{n:10s}" for n in CLASS_NAMES]))
    print("  " + "-" * 45)
    for i, name in enumerate(CLASS_NAMES):
        row = " | ".join([f"{v:10d}" for v in cm[i]])
        print(f"  {name:12s} | {row}")
    
    # Cross-validation
    print("\n  Cross-Validation (5-fold):")
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
    cv_scores = cross_val_score(model, np.vstack([X_train, X_test]), 
                                 np.hstack([y_train, y_test]), 
                                 cv=cv, scoring='accuracy')
    print(f"    Mean CV Accuracy: {cv_scores.mean():.4f} (+/- {cv_scores.std()*2:.4f})")
    
    # Return metrics for saving
    metrics = {
        'target_source': 'teacher_labelled',
        'training_accuracy': float(train_acc),
        'test_accuracy': float(test_acc),
        'overfitting_gap': float(train_acc - test_acc),
        'precision_macro': float(precision.mean()),
        'recall_macro': float(recall.mean()),
        'f1_macro': float(f1.mean()),
        'f1_weighted': float(f1_score(y_test, y_pred, average='weighted')),
        'auc_roc_weighted': float(auc_roc) if auc_roc is not None else None,
        'cv_mean': float(cv_scores.mean()),
        'cv_std': float(cv_scores.std()),
        'per_class': {
            name: {
                'precision': float(precision[i]),
                'recall': float(recall[i]),
                'f1': float(f1[i])
            } for i, name in enumerate(CLASS_NAMES)
        },
        'confusion_matrix': cm.tolist()
    }
    
    return metrics


def compute_feature_importance(model, X_train, y_train, X_test, y_test):
    """Calculate and display feature importance."""
    
    print("\n" + "=" * 50)
    print("FEATURE IMPORTANCE ANALYSIS")
    print("=" * 50)
    
    # Method 1: XGBoost built-in feature importance (gain-based)
    print("\n  [Method 1] XGBoost Feature Importance (Gain):")
    
    mean_importance = model.feature_importances_
    
    # Create list of (index, importance) and sort
    importance_list = [(i, mean_importance[i]) for i in range(len(FEATURE_COLUMNS))]
    importance_list.sort(key=lambda x: x[1], reverse=True)
    
    print("  " + "-" * 50)
    gini_ranks = {}
    for rank, (idx, imp) in enumerate(importance_list, 1):
        name = FEATURE_COLUMNS[idx]
        gini_ranks[idx] = rank
        bar = "█" * int(imp * 50)
        print(f"    {rank:2d}. {name:25s} {imp:.4f} {bar}")
    
    # Method 2: Permutation Importance
    print("\n  [Method 2] Permutation Importance (on test set):")
    
    perm_importance = permutation_importance(
        model, X_test, y_test, 
        n_repeats=10, 
        random_state=42,
        n_jobs=-1
    )
    
    # Create list of (index, importance) and sort
    perm_list = [(i, perm_importance.importances_mean[i], perm_importance.importances_std[i]) 
                 for i in range(len(FEATURE_COLUMNS))]
    perm_list.sort(key=lambda x: x[1], reverse=True)
    
    print("  " + "-" * 50)
    perm_ranks = {}
    for rank, (idx, imp, std) in enumerate(perm_list, 1):
        name = FEATURE_COLUMNS[idx]
        perm_ranks[idx] = rank
        bar = "█" * int(imp * 100)
        print(f"    {rank:2d}. {name:25s} {imp:.4f} (±{std:.4f}) {bar}")
    
    # Return importance dict for saving
    importance_dict = {
        'xgboost_importance': {},
        'permutation_importance': {}
    }
    
    for i, feat_name in enumerate(FEATURE_COLUMNS):
        importance_dict['xgboost_importance'][feat_name] = {
            'mean': float(mean_importance[i]),
            'rank': gini_ranks.get(i, len(FEATURE_COLUMNS))
        }
        importance_dict['permutation_importance'][feat_name] = {
            'mean': float(perm_importance.importances_mean[i]),
            'std': float(perm_importance.importances_std[i]),
            'rank': perm_ranks.get(i, len(FEATURE_COLUMNS))
        }
    
    return importance_dict


def save_model_artifacts(model, scaler, metrics, importance, output_dir: str):
    """Save model and all related artifacts."""
    
    print("\n" + "=" * 50)
    print("SAVING MODEL ARTIFACTS")
    print("=" * 50)
    
    os.makedirs(output_dir, exist_ok=True)
    
    # Save model
    model_path = os.path.join(output_dir, 'xscaffold_xgboost_model.pkl')
    joblib.dump(model, model_path)
    print(f"  ✅ Model saved: {model_path}")
    
    # Save scaler
    scaler_path = os.path.join(output_dir, 'xscaffold_scaler.pkl')
    joblib.dump(scaler, scaler_path)
    print(f"  ✅ Scaler saved: {scaler_path}")
    
    # Save feature names
    features_path = os.path.join(output_dir, 'feature_names.json')
    with open(features_path, 'w') as f:
        json.dump({
            'features': FEATURE_COLUMNS,
            'target': TARGET_COLUMN,
            'class_names': CLASS_NAMES
        }, f, indent=2)
    print(f"  ✅ Feature names saved: {features_path}")
    
    # Save metrics
    metrics_path = os.path.join(output_dir, 'model_metrics.json')
    with open(metrics_path, 'w') as f:
        json.dump(metrics, f, indent=2)
    print(f"  ✅ Metrics saved: {metrics_path}")
    
    # Save feature importance
    importance_path = os.path.join(output_dir, 'feature_importance.json')
    with open(importance_path, 'w') as f:
        json.dump(importance, f, indent=2)
    print(f"  ✅ Feature importance saved: {importance_path}")
    
    # Save model config
    config_path = os.path.join(output_dir, 'model_config.json')
    with open(config_path, 'w') as f:
        json.dump({
            'model_type': 'XGBClassifier',
            'algorithm': 'XGBoost (Extreme Gradient Boosting)',
            'xgboost_config': XGBOOST_CONFIG,
            'training_date': datetime.now().isoformat(),
            'xgboost_version': xgb.__version__
        }, f, indent=2)
    print(f"  ✅ Config saved: {config_path}")


def main():
    """Main training pipeline."""
    
    print("\n" + "=" * 60)
    print("X-SCAFFOLD ML TRAINING PIPELINE")
    print("XGBoost Classifier for Mastery Level Prediction")
    print("=" * 60)
    
    # Paths
    script_dir = os.path.dirname(os.path.abspath(__file__))
    dataset_path = os.path.join(script_dir, 'xscaffold_student_dataset.csv')
    output_dir = script_dir
    
    # Step 1: Load dataset
    X, y, df = load_dataset(dataset_path)
    
    # Step 2: Split data
    print("\nSplitting dataset (80% train, 20% test)...")
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )
    print(f"  Training samples: {len(X_train)}")
    print(f"  Test samples: {len(X_test)}")
    
    # Step 3: Scale features
    print("\nScaling features (StandardScaler)...")
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)
    
    # Step 4: Train model
    model = train_xgboost_classifier(X_train_scaled, y_train)
    
    # Step 5: Evaluate model
    metrics = evaluate_model(model, X_test_scaled, y_test, X_train_scaled, y_train)
    
    # Step 6: Feature importance
    importance = compute_feature_importance(
        model, X_train_scaled, y_train, X_test_scaled, y_test
    )
    
    # Step 7: Save artifacts
    save_model_artifacts(model, scaler, metrics, importance, output_dir)
    
    print("\n" + "=" * 60)
    print("TRAINING COMPLETE")
    print("=" * 60)
    print(f"\n  Final Test Accuracy: {metrics['test_accuracy']:.4f}")
    print(f"  F1 Score (Weighted): {metrics['f1_weighted']:.4f}")
    print(f"  Cross-Val Accuracy:  {metrics['cv_mean']:.4f} (±{metrics['cv_std']*2:.4f})")
    print("\n  Model ready for deployment!")
    
    return model, scaler, metrics


if __name__ == "__main__":
    model, scaler, metrics = main()