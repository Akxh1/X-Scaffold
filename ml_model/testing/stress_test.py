# %% [markdown]
# # X-Scaffold ML Stress Testing Suite
# **Comprehensive validation of XGBoost & Gradient Boosting**
#
# **Target Variable:** Teacher-assigned mastery labels (v3.0)
# **Data:** 81 real teacher-rated records → Cholesky → KNN label propagation
#
# 6 Test Sections:
# 1. Stratified k-Fold Cross-Validation
# 2. Model Comparison Tournament + Statistical Significance
# 3. Hyperparameter Optimization (Random Search + Optuna)
# 4. Sensitivity & Robustness Testing
# 5. Residual / Error Analysis
# 6. STRESS: Feature Importance Audit
#
# **Usage**: Run cell-by-cell in Google Colab.
# Upload `student_research_data_teacher_reviewed.csv`.

# %%
# =====================================================
# SETUP & DEPENDENCIES
# =====================================================
import subprocess, sys
for pkg in ['xgboost', 'optuna', 'shap']:
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', '-q', pkg])

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
import time
import warnings
warnings.filterwarnings('ignore')

from sklearn.preprocessing import StandardScaler, label_binarize
from sklearn.model_selection import (
    StratifiedKFold, cross_val_score, cross_val_predict,
    RandomizedSearchCV, train_test_split
)
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    roc_auc_score, confusion_matrix, classification_report,
    roc_curve, auc
)
from sklearn.dummy import DummyClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from xgboost import XGBClassifier
from scipy import stats
import optuna
optuna.logging.set_verbosity(optuna.logging.WARNING)

np.random.seed(42)

FEATURE_COLUMNS = [
    'score_percentage', 'hard_question_accuracy', 'hint_usage_percentage',
    'avg_confidence', 'answer_changes_rate', 'tab_switches_rate',
    'avg_time_per_question', 'review_percentage', 'avg_first_action_latency',
    'clicks_per_question', 'performance_trend'
]
CLASS_NAMES = ['at_risk', 'developing', 'proficient', 'advanced']

FEATURE_CONSTRAINTS = {
    'score_percentage': (0, 100), 'hard_question_accuracy': (0, 100),
    'hint_usage_percentage': (0, 100), 'avg_confidence': (1, 5),
    'answer_changes_rate': (0, 5), 'tab_switches_rate': (0, 10),
    'avg_time_per_question': (1, 300), 'review_percentage': (0, 100),
    'avg_first_action_latency': (0.5, 60), 'clicks_per_question': (1, 50),
    'performance_trend': (-1, 1)
}

# --- Helper: Assign teacher labels via KNN ---
TEACHER_LABEL_MAP = {'At Risk': 0, 'Developing': 1, 'Proficient': 2, 'Advanced': 3}
META_NAMES = {0: 'at_risk', 1: 'developing', 2: 'proficient', 3: 'advanced'}

def assign_teacher_labels(df, real_df):
    """Assign teacher labels to synthetic data via KNN on real teacher-rated records."""
    from sklearn.neighbors import KNeighborsClassifier
    df = df.copy()
    knn = KNeighborsClassifier(n_neighbors=5, weights='distance')
    knn.fit(real_df[FEATURE_COLUMNS].values, real_df['mastery_level'].values)
    df['mastery_level'] = knn.predict(df[FEATURE_COLUMNS].values)
    df['mastery_level_name'] = df['mastery_level'].map(META_NAMES)
    return df

# --- Helper: LMS Calculation (FALLBACK ONLY — from generate_dataset.py) ---
def calculate_lms(df):
    """FALLBACK: Calculate LMS using formula. Used only for comparison, not as target."""
    df = df.copy()
    S = df['score_percentage']
    Hd = df['hard_question_accuracy'] / 100
    expected_conf = 1 + (df['score_percentage'] / 25)
    Ccal = np.where(np.abs(df['avg_confidence'] - expected_conf) <= 1, 1, 0)
    Ks = np.clip(1 - (df['answer_changes_rate'] - 0.5) / 1.0, 0, 1)
    Af = np.clip(1 - (df['tab_switches_rate'] - 1) / 2.0, 0, 1)
    Hu = df['hint_usage_percentage'] / 100
    df['learning_mastery_score'] = (
        0.50 * S + 0.15 * (Hd * 100) + 10 * Ccal + 10 * Ks + 10 * Af - 15 * np.power(Hu, 1.5)
    ).round(1).clip(0, 100)
    def classify(lms):
        if lms < 36: return 0
        elif lms < 56: return 1
        elif lms < 76: return 2
        else: return 3
    df['mastery_level'] = df['learning_mastery_score'].apply(classify)
    df['mastery_level_name'] = df['mastery_level'].map(META_NAMES)
    return df

# --- Helper: Synthetic Generation (Cholesky + KNN) ---
def generate_synthetic_from_real(real_df, n_students=2000):
    """Generate synthetic features via Cholesky, then assign teacher labels via KNN."""
    means = real_df[FEATURE_COLUMNS].mean()
    stds = real_df[FEATURE_COLUMNS].std()
    corr = real_df[FEATURE_COLUMNS].corr().values + np.eye(len(FEATURE_COLUMNS)) * 0.001
    L = np.linalg.cholesky(corr)
    uncorr = np.random.normal(0, 1, (n_students, len(FEATURE_COLUMNS)))
    synth = pd.DataFrame(uncorr @ L.T * stds.values + means.values, columns=FEATURE_COLUMNS)
    for col, (lo, hi) in FEATURE_CONSTRAINTS.items():
        if col in synth.columns:
            synth[col] = synth[col].clip(lo, hi).round(2)
    return assign_teacher_labels(synth, real_df)

print("=" * 70)
print("  X-SCAFFOLD ML STRESS TESTING SUITE")
print("=" * 70)

# %%
# =====================================================
# DATA LOADING & SYNTHETIC GENERATION
# =====================================================
# For Google Colab: upload student_research_data_teacher_reviewed.csv first
# from google.colab import files
# uploaded = files.upload()

real_df = pd.read_csv('student_research_data_teacher_reviewed.csv')
real_df['mastery_level'] = real_df['Teacher Rating'].map(TEACHER_LABEL_MAP)
print(f"✅ Real data: {len(real_df)} records with teacher labels")

# Generate synthetic data from real records (Cholesky + KNN)
print("⏳ Generating synthetic dataset from real data...")
synth_df = generate_synthetic_from_real(real_df, n_students=2000)
print(f"✅ Synthetic data: {len(synth_df)} records generated")

# Prepare features and targets from synthetic data
X = synth_df[FEATURE_COLUMNS].values
y = synth_df['mastery_level'].values

scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)
X_train, X_test, y_train, y_test = train_test_split(
    X_scaled, y, test_size=0.2, random_state=42, stratify=y
)

print(f"\nTrain: {len(X_train)} | Test: {len(X_test)}")
print(f"Class distribution: {dict(zip(*np.unique(y, return_counts=True)))}")

# Define the two champion models
xgb_model = XGBClassifier(
    n_estimators=50, max_depth=8, learning_rate=0.1,
    eval_metric='mlogloss', random_state=42, use_label_encoder=False
)
gb_model = GradientBoostingClassifier(
    n_estimators=50, max_depth=8, learning_rate=0.1,
    min_samples_split=10, random_state=42
)
TOP_MODELS = {'XGBoost': xgb_model, 'Gradient Boosting': gb_model}

# %% [markdown]
# ---
# ## TEST 1: Stratified k-Fold Cross-Validation (The "Fairness" Test)

# %%
print("\n" + "=" * 70)
print("  TEST 1: STRATIFIED k-FOLD CROSS-VALIDATION")
print("=" * 70)

for k in [5, 10]:
    print(f"\n{'─' * 50}")
    print(f"  {k}-Fold Stratified Cross-Validation")
    print(f"{'─' * 50}")
    cv = StratifiedKFold(n_splits=k, shuffle=True, random_state=42)

    for name, model in TOP_MODELS.items():
        scores = cross_val_score(model, X_scaled, y, cv=cv, scoring='accuracy')
        print(f"\n  {name}:")
        print(f"    Per-fold: {[f'{s:.4f}' for s in scores]}")
        print(f"    Mean:     {scores.mean():.4f} ± {scores.std():.4f}")
        print(f"    Range:    {scores.min():.4f} – {scores.max():.4f}")
        print(f"    Spread:   {(scores.max() - scores.min()):.4f}")

# Visualization: CV Fold Scores
fig, axes = plt.subplots(1, 2, figsize=(14, 5))
for ax_idx, k in enumerate([5, 10]):
    cv = StratifiedKFold(n_splits=k, shuffle=True, random_state=42)
    ax = axes[ax_idx]
    for name, model in TOP_MODELS.items():
        scores = cross_val_score(model, X_scaled, y, cv=cv, scoring='accuracy')
        ax.plot(range(1, k+1), scores, 'o-', label=f'{name} (μ={scores.mean():.3f})', linewidth=2)
    ax.set_xlabel('Fold')
    ax.set_ylabel('Accuracy')
    ax.set_title(f'{k}-Fold Cross-Validation', fontweight='bold')
    ax.legend()
    ax.grid(True, alpha=0.3)
    ax.set_ylim(0.80, 1.0)
plt.tight_layout()
plt.savefig('stress_1_cv_folds.png', dpi=150, bbox_inches='tight')
plt.show()
print("✅ Saved: stress_1_cv_folds.png")

# %% [markdown]
# ---
# ## TEST 2: Model Comparison Tournament + Statistical Significance

# %%
print("\n" + "=" * 70)
print("  TEST 2: MODEL COMPARISON TOURNAMENT")
print("=" * 70)

tournament_models = {
    'Baseline (Most Frequent)': DummyClassifier(strategy='most_frequent'),
    'Baseline (Stratified)': DummyClassifier(strategy='stratified', random_state=42),
    'Logistic Regression': LogisticRegression(max_iter=1000, random_state=42),
    'Random Forest': RandomForestClassifier(n_estimators=50, max_depth=8, random_state=42),
    'Gradient Boosting': GradientBoostingClassifier(n_estimators=50, max_depth=8, learning_rate=0.1, random_state=42),
    'XGBoost': XGBClassifier(n_estimators=50, max_depth=8, learning_rate=0.1, eval_metric='mlogloss', random_state=42, use_label_encoder=False),
}

cv = StratifiedKFold(n_splits=10, shuffle=True, random_state=42)
cv_results = {}

print("\n  Model Tournament (10-Fold Stratified CV):")
print("  " + "─" * 55)
for name, model in tournament_models.items():
    scores = cross_val_score(model, X_scaled, y, cv=cv, scoring='accuracy')
    cv_results[name] = scores
    bar = "█" * int(scores.mean() * 40)
    star = " ⭐" if name == 'XGBoost' else ""
    print(f"  {name:30s} {scores.mean():.4f} ± {scores.std():.4f}  {bar}{star}")

# --- Statistical Significance Tests ---
print(f"\n{'─' * 50}")
print("  STATISTICAL SIGNIFICANCE TESTS")
print(f"{'─' * 50}")

# Paired T-Test: XGBoost vs Gradient Boosting
xgb_scores = cv_results['XGBoost']
gb_scores = cv_results['Gradient Boosting']
t_stat, p_value = stats.ttest_rel(xgb_scores, gb_scores)
print(f"\n  Paired T-Test (XGBoost vs Gradient Boosting):")
print(f"    t-statistic: {t_stat:.4f}")
print(f"    p-value:     {p_value:.6f}")
print(f"    Significant (α=0.05)? {'YES ✅' if p_value < 0.05 else 'NO ❌'}")
print(f"    Mean diff:   {xgb_scores.mean() - gb_scores.mean():.4f}")

# Paired T-Test: XGBoost vs all others
print(f"\n  Paired T-Tests (XGBoost vs all):")
for name, scores in cv_results.items():
    if name == 'XGBoost':
        continue
    t, p = stats.ttest_rel(xgb_scores, scores)
    sig = "✅ Sig." if p < 0.05 else "❌ N.S."
    print(f"    vs {name:30s} p={p:.6f} {sig}")

# McNemar's Test: XGBoost vs Gradient Boosting
print(f"\n  McNemar's Test (XGBoost vs Gradient Boosting):")
xgb_full = XGBClassifier(n_estimators=50, max_depth=8, learning_rate=0.1,
                          eval_metric='mlogloss', random_state=42, use_label_encoder=False)
gb_full = GradientBoostingClassifier(n_estimators=50, max_depth=8, learning_rate=0.1, random_state=42)
xgb_full.fit(X_train, y_train)
gb_full.fit(X_train, y_train)
xgb_pred = xgb_full.predict(X_test)
gb_pred = gb_full.predict(X_test)

xgb_correct = (xgb_pred == y_test)
gb_correct = (gb_pred == y_test)
# Build contingency: [both_right, xgb_right_gb_wrong], [xgb_wrong_gb_right, both_wrong]
b = np.sum(xgb_correct & ~gb_correct)   # XGBoost right, GB wrong
c = np.sum(~xgb_correct & gb_correct)   # XGBoost wrong, GB right
# McNemar's chi-squared (with continuity correction)
if b + c > 0:
    mcnemar_stat = (abs(b - c) - 1) ** 2 / (b + c)
    mcnemar_p = 1 - stats.chi2.cdf(mcnemar_stat, df=1)
else:
    mcnemar_stat, mcnemar_p = 0, 1.0
print(f"    Discordant pairs: b={b} (XGB✓ GB✗), c={c} (XGB✗ GB✓)")
print(f"    χ² statistic:    {mcnemar_stat:.4f}")
print(f"    p-value:         {mcnemar_p:.6f}")
print(f"    Significant?     {'YES ✅' if mcnemar_p < 0.05 else 'NO ❌'}")

# Visualization: Tournament Bar Chart
fig, ax = plt.subplots(figsize=(12, 6))
names = list(cv_results.keys())
means = [cv_results[n].mean() for n in names]
stds = [cv_results[n].std() for n in names]
colors = ['#2196F3' if n == 'XGBoost' else '#90CAF9' for n in names]
bars = ax.barh(names, means, xerr=stds, color=colors, edgecolor='white', height=0.6, capsize=4)
for bar, m in zip(bars, means):
    ax.text(m + 0.005, bar.get_y() + bar.get_height()/2, f'{m:.4f}', va='center', fontweight='bold')
ax.set_xlabel('Accuracy (10-Fold CV)')
ax.set_title('Model Comparison Tournament', fontsize=16, fontweight='bold')
ax.set_xlim(0, 1.05)
ax.invert_yaxis()
plt.tight_layout()
plt.savefig('stress_2_tournament.png', dpi=150, bbox_inches='tight')
plt.show()
print("✅ Saved: stress_2_tournament.png")

# %% [markdown]
# ---
# ## TEST 3: Hyperparameter Optimization

# %%
print("\n" + "=" * 70)
print("  TEST 3: HYPERPARAMETER OPTIMIZATION")
print("=" * 70)

# --- 3a: RandomizedSearchCV for XGBoost ---
print("\n  3a) RandomizedSearchCV for XGBoost...")
param_dist = {
    'n_estimators': [25, 50, 100, 150, 200],
    'max_depth': [3, 5, 8, 10, 12],
    'learning_rate': [0.01, 0.05, 0.1, 0.2, 0.3],
    'subsample': [0.6, 0.7, 0.8, 0.9, 1.0],
    'colsample_bytree': [0.6, 0.7, 0.8, 0.9, 1.0],
    'reg_alpha': [0, 0.01, 0.1, 1.0],
    'reg_lambda': [0.5, 1.0, 2.0, 5.0],
}
random_search = RandomizedSearchCV(
    XGBClassifier(eval_metric='mlogloss', random_state=42, use_label_encoder=False),
    param_distributions=param_dist, n_iter=50, cv=5, scoring='accuracy',
    random_state=42, n_jobs=-1, verbose=0
)
random_search.fit(X_train, y_train)
print(f"    Best Score (CV): {random_search.best_score_:.4f}")
print(f"    Best Params: {random_search.best_params_}")
rs_test_acc = accuracy_score(y_test, random_search.best_estimator_.predict(X_test))
print(f"    Test Accuracy:   {rs_test_acc:.4f}")

# --- 3b: Optuna Bayesian Optimization ---
print("\n  3b) Optuna Bayesian Optimization for XGBoost...")

def objective(trial):
    params = {
        'n_estimators': trial.suggest_int('n_estimators', 25, 300),
        'max_depth': trial.suggest_int('max_depth', 3, 15),
        'learning_rate': trial.suggest_float('learning_rate', 0.005, 0.3, log=True),
        'subsample': trial.suggest_float('subsample', 0.5, 1.0),
        'colsample_bytree': trial.suggest_float('colsample_bytree', 0.5, 1.0),
        'reg_alpha': trial.suggest_float('reg_alpha', 1e-3, 10.0, log=True),
        'reg_lambda': trial.suggest_float('reg_lambda', 1e-3, 10.0, log=True),
        'min_child_weight': trial.suggest_int('min_child_weight', 1, 10),
    }
    model = XGBClassifier(**params, eval_metric='mlogloss', random_state=42, use_label_encoder=False)
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
    scores = cross_val_score(model, X_train, y_train, cv=cv, scoring='accuracy')
    return scores.mean()

study = optuna.create_study(direction='maximize')
study.optimize(objective, n_trials=100, show_progress_bar=True)
print(f"    Best Score (CV): {study.best_value:.4f}")
print(f"    Best Params: {study.best_params}")
optuna_model = XGBClassifier(**study.best_params, eval_metric='mlogloss', random_state=42, use_label_encoder=False)
optuna_model.fit(X_train, y_train)
optuna_test_acc = accuracy_score(y_test, optuna_model.predict(X_test))
print(f"    Test Accuracy:   {optuna_test_acc:.4f}")

# --- 3c: Comparison ---
default_model = XGBClassifier(n_estimators=50, max_depth=8, learning_rate=0.1,
                               eval_metric='mlogloss', random_state=42, use_label_encoder=False)
default_model.fit(X_train, y_train)
default_acc = accuracy_score(y_test, default_model.predict(X_test))

print(f"\n  Optimization Comparison:")
print(f"  {'─' * 45}")
print(f"  {'Method':30s} {'Test Accuracy':>12s}")
print(f"  {'─' * 45}")
print(f"  {'Default XGBoost':30s} {default_acc:12.4f}")
print(f"  {'RandomizedSearchCV':30s} {rs_test_acc:12.4f}")
print(f"  {'Optuna Bayesian':30s} {optuna_test_acc:12.4f}")
print(f"  {'─' * 45}")

# Visualization: Optuna optimization history
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(14, 5))
trials = study.trials
ax1.plot([t.number for t in trials], [t.value for t in trials], 'o', alpha=0.4, markersize=4)
ax1.plot([t.number for t in trials],
         pd.Series([t.value for t in trials]).cummax(), 'r-', linewidth=2, label='Best so far')
ax1.set_xlabel('Trial')
ax1.set_ylabel('CV Accuracy')
ax1.set_title('Optuna: Optimization History', fontweight='bold')
ax1.legend()
ax1.grid(True, alpha=0.3)

comp = {'Default': default_acc, 'RandomSearch': rs_test_acc, 'Optuna': optuna_test_acc}
colors_comp = ['#90CAF9', '#42A5F5', '#1565C0']
bars = ax2.bar(comp.keys(), comp.values(), color=colors_comp, edgecolor='white')
for bar, v in zip(bars, comp.values()):
    ax2.text(bar.get_x() + bar.get_width()/2, v + 0.002, f'{v:.4f}', ha='center', fontweight='bold')
ax2.set_ylabel('Test Accuracy')
ax2.set_title('Hyperparameter Optimization Comparison', fontweight='bold')
ax2.set_ylim(min(comp.values()) - 0.02, max(comp.values()) + 0.02)
plt.tight_layout()
plt.savefig('stress_3_optimization.png', dpi=150, bbox_inches='tight')
plt.show()
print("✅ Saved: stress_3_optimization.png")

# %% [markdown]
# ---
# ## TEST 4: Sensitivity & Robustness Testing

# %%
print("\n" + "=" * 70)
print("  TEST 4: SENSITIVITY & ROBUSTNESS TESTING")
print("=" * 70)

# --- 4a: Perturbation Testing ---
print("\n  4a) Perturbation Testing (Gaussian Noise)")
print("  " + "─" * 55)

# Train fresh models for perturbation
xgb_pert = XGBClassifier(n_estimators=50, max_depth=8, learning_rate=0.1,
                          eval_metric='mlogloss', random_state=42, use_label_encoder=False)
gb_pert = GradientBoostingClassifier(n_estimators=50, max_depth=8, learning_rate=0.1, random_state=42)
xgb_pert.fit(X_train, y_train)
gb_pert.fit(X_train, y_train)

noise_levels = [0.0, 0.05, 0.10, 0.15, 0.20, 0.25, 0.30]
perturbation_results = {name: [] for name in ['XGBoost', 'Gradient Boosting']}

for noise in noise_levels:
    X_noisy = X_test + np.random.normal(0, noise, X_test.shape)
    perturbation_results['XGBoost'].append(accuracy_score(y_test, xgb_pert.predict(X_noisy)))
    perturbation_results['Gradient Boosting'].append(accuracy_score(y_test, gb_pert.predict(X_noisy)))

print(f"  {'Noise Level':>12s}  {'XGBoost':>10s}  {'Grad.Boost':>10s}  {'Diff':>8s}")
print(f"  {'─' * 45}")
for i, noise in enumerate(noise_levels):
    xgb_a = perturbation_results['XGBoost'][i]
    gb_a = perturbation_results['Gradient Boosting'][i]
    print(f"  {noise:12.0%}  {xgb_a:10.4f}  {gb_a:10.4f}  {xgb_a - gb_a:+8.4f}")

# Degradation rate
xgb_drop = perturbation_results['XGBoost'][0] - perturbation_results['XGBoost'][-1]
gb_drop = perturbation_results['Gradient Boosting'][0] - perturbation_results['Gradient Boosting'][-1]
print(f"\n  Total degradation (0% → 30% noise):")
print(f"    XGBoost:          {xgb_drop:+.4f} ({'ROBUST ✅' if xgb_drop < 0.10 else 'SENSITIVE ⚠️'})")
print(f"    Gradient Boosting:{gb_drop:+.4f} ({'ROBUST ✅' if gb_drop < 0.10 else 'SENSITIVE ⚠️'})")

# --- 4b: Data Drift Simulation ---
print(f"\n  4b) Data Drift Simulation (Feature Shift ±1σ)")
print("  " + "─" * 55)

drift_scenarios = {
    'No Drift': {},
    'Scores ↑ +1σ': {'score_percentage': 1.0},
    'Scores ↓ -1σ': {'score_percentage': -1.0},
    'Hints ↑ +1σ': {'hint_usage_percentage': 1.0},
    'Confidence ↓ -1σ': {'avg_confidence': -1.0},
    'Multi-feature drift': {'score_percentage': 0.5, 'hint_usage_percentage': 0.5, 'tab_switches_rate': 0.5},
}

# Get feature standard deviations from original data
feature_stds = synth_df[FEATURE_COLUMNS].std().values

print(f"  {'Scenario':30s}  {'XGBoost':>10s}  {'Grad.Boost':>10s}")
print(f"  {'─' * 55}")
for scenario_name, shifts in drift_scenarios.items():
    X_drifted = X_test.copy()
    for feat_name, sigma_shift in shifts.items():
        feat_idx = FEATURE_COLUMNS.index(feat_name)
        X_drifted[:, feat_idx] += sigma_shift  # Already scaled, so +1 = +1σ
    xgb_a = accuracy_score(y_test, xgb_pert.predict(X_drifted))
    gb_a = accuracy_score(y_test, gb_pert.predict(X_drifted))
    print(f"  {scenario_name:30s}  {xgb_a:10.4f}  {gb_a:10.4f}")

# Visualization: Perturbation curves
fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(14, 5))
for name, results in perturbation_results.items():
    ax1.plot([n*100 for n in noise_levels], results, 'o-', label=name, linewidth=2)
ax1.set_xlabel('Noise Level (%)')
ax1.set_ylabel('Accuracy')
ax1.set_title('Perturbation Robustness', fontweight='bold')
ax1.legend()
ax1.grid(True, alpha=0.3)
ax1.axhline(y=0.5, color='red', linestyle='--', alpha=0.3, label='Random baseline')

# Drift bar chart
scenarios = list(drift_scenarios.keys())
xgb_drift_scores = []
gb_drift_scores = []
for scenario_name, shifts in drift_scenarios.items():
    X_drifted = X_test.copy()
    for feat_name, sigma_shift in shifts.items():
        feat_idx = FEATURE_COLUMNS.index(feat_name)
        X_drifted[:, feat_idx] += sigma_shift
    xgb_drift_scores.append(accuracy_score(y_test, xgb_pert.predict(X_drifted)))
    gb_drift_scores.append(accuracy_score(y_test, gb_pert.predict(X_drifted)))

x = np.arange(len(scenarios))
ax2.bar(x - 0.15, xgb_drift_scores, 0.3, label='XGBoost', color='#2196F3')
ax2.bar(x + 0.15, gb_drift_scores, 0.3, label='Gradient Boosting', color='#FF9800')
ax2.set_xticks(x)
ax2.set_xticklabels(scenarios, rotation=35, ha='right', fontsize=8)
ax2.set_ylabel('Accuracy')
ax2.set_title('Data Drift Simulation', fontweight='bold')
ax2.legend()
ax2.grid(True, alpha=0.3, axis='y')
plt.tight_layout()
plt.savefig('stress_4_robustness.png', dpi=150, bbox_inches='tight')
plt.show()
print("✅ Saved: stress_4_robustness.png")

# %% [markdown]
# ---
# ## TEST 5: Residual / Error Analysis (The "Error" Audit)

# %%
print("\n" + "=" * 70)
print("  TEST 5: RESIDUAL / ERROR ANALYSIS")
print("=" * 70)

for name, model in [('XGBoost', xgb_pert), ('Gradient Boosting', gb_pert)]:
    y_pred = model.predict(X_test)
    cm = confusion_matrix(y_test, y_pred)
    report = classification_report(y_test, y_pred, target_names=CLASS_NAMES, output_dict=True)

    print(f"\n  {name} — Detailed Classification Report:")
    print(f"  {'─' * 60}")
    print(classification_report(y_test, y_pred, target_names=CLASS_NAMES, digits=4))

    # Misclassification analysis
    misclassified = np.where(y_pred != y_test)[0]
    print(f"  Misclassified: {len(misclassified)}/{len(y_test)} ({len(misclassified)/len(y_test):.1%})")

    if len(misclassified) > 0:
        print(f"\n  Misclassification Patterns:")
        for true_class in range(4):
            for pred_class in range(4):
                if true_class != pred_class and cm[true_class][pred_class] > 0:
                    print(f"    {CLASS_NAMES[true_class]:12s} → {CLASS_NAMES[pred_class]:12s}: "
                          f"{cm[true_class][pred_class]:3d} cases")

# Visualization: Confusion Matrices
fig, axes = plt.subplots(1, 2, figsize=(14, 6))
for ax, (name, model) in zip(axes, [('XGBoost', xgb_pert), ('Gradient Boosting', gb_pert)]):
    y_pred = model.predict(X_test)
    cm = confusion_matrix(y_test, y_pred)
    cm_pct = cm.astype('float') / cm.sum(axis=1)[:, np.newaxis] * 100

    sns.heatmap(cm, annot=True, fmt='d', cmap='Blues', ax=ax,
                xticklabels=CLASS_NAMES, yticklabels=CLASS_NAMES,
                cbar_kws={'shrink': 0.6})
    # Add percentages
    for i in range(4):
        for j in range(4):
            ax.text(j + 0.5, i + 0.75, f'({cm_pct[i][j]:.0f}%)',
                    ha='center', va='center', fontsize=7, color='gray')
    ax.set_xlabel('Predicted')
    ax.set_ylabel('Actual')
    ax.set_title(f'{name} — Confusion Matrix', fontweight='bold')
plt.tight_layout()
plt.savefig('stress_5_confusion.png', dpi=150, bbox_inches='tight')
plt.show()
print("✅ Saved: stress_5_confusion.png")



# %% [markdown]
# ---
# ## TEST 6: STRESS — Feature Importance Audit

# %%
print("\n" + "=" * 70)
print("  TEST 6: STRESS — FEATURE IMPORTANCE AUDIT")
print("=" * 70)

import shap
from sklearn.inspection import permutation_importance

# Train on full synthetic data
xgb_audit = XGBClassifier(n_estimators=50, max_depth=8, learning_rate=0.1,
                           eval_metric='mlogloss', random_state=42, use_label_encoder=False)
xgb_audit.fit(X_train, y_train)

# --- 8a: Built-in XGBoost Feature Importance (Gain) ---
print("\n  8a) XGBoost Built-in Feature Importance (Gain)")
print("  " + "─" * 55)
importances = xgb_audit.feature_importances_
sorted_idx = np.argsort(importances)[::-1]

print(f"  {'Rank':>4s}  {'Feature':30s}  {'Importance':>10s}  {'Share':>8s}")
print(f"  {'─' * 58}")
for rank, idx in enumerate(sorted_idx, 1):
    share = importances[idx] / importances.sum()
    bar = "█" * int(share * 30)
    print(f"  {rank:4d}  {FEATURE_COLUMNS[idx]:30s}  {importances[idx]:10.4f}  {share:7.1%}  {bar}")

# Check concentration
top_feat_share = importances[sorted_idx[0]] / importances.sum()
top_2_share = importances[sorted_idx[:2]].sum() / importances.sum()
print(f"\n  ⚠️ Concentration Check:")
print(f"    Top feature:    {top_feat_share:.1%} {'⚠️ OVER 50%!' if top_feat_share > 0.5 else '✅ OK'}")
print(f"    Top 2 features: {top_2_share:.1%} {'⚠️ OVER 70%!' if top_2_share > 0.7 else '✅ OK'}")

# --- 8b: Permutation Importance ---
print(f"\n  8b) Permutation Importance (model-agnostic)")
print("  " + "─" * 55)
perm_imp = permutation_importance(xgb_audit, X_test, y_test, n_repeats=30, random_state=42)
perm_sorted = np.argsort(perm_imp.importances_mean)[::-1]

print(f"  {'Rank':>4s}  {'Feature':30s}  {'Importance':>10s}  {'Std':>8s}")
print(f"  {'─' * 58}")
for rank, idx in enumerate(perm_sorted, 1):
    print(f"  {rank:4d}  {FEATURE_COLUMNS[idx]:30s}  "
          f"{perm_imp.importances_mean[idx]:10.4f}  {perm_imp.importances_std[idx]:8.4f}")

# --- 8c: SHAP Values ---
print(f"\n  8c) SHAP Feature Importance (TreeExplainer)")
print("  " + "─" * 55)
explainer = shap.TreeExplainer(xgb_audit)
shap_values = explainer.shap_values(X_test)

# For multi-class: average absolute SHAP across all classes
if isinstance(shap_values, list):
    shap_mean = np.mean([np.abs(sv).mean(axis=0) for sv in shap_values], axis=0)
else:
    # If it's a single 3D array (n_samples, n_features, n_classes)
    # First average across classes (axis=2), then across samples (axis=0)
    shap_mean = np.abs(shap_values).mean(axis=2).mean(axis=0)

shap_sorted = np.argsort(shap_mean)[::-1]
print(f"  {'Rank':>4s}  {'Feature':30s}  {'Mean |SHAP|':>12s}")
print(f"  {'─' * 50}")
for rank, idx in enumerate(shap_sorted, 1):
    print(f"  {rank:4d}  {FEATURE_COLUMNS[idx]:30s}  {shap_mean[idx]:12.4f}")

# --- 8d: Agreement Check ---
print(f"\n  8d) Feature Ranking Agreement")
print("  " + "─" * 55)
top5_gain = set([FEATURE_COLUMNS[i] for i in sorted_idx[:5]])
top5_perm = set([FEATURE_COLUMNS[i] for i in perm_sorted[:5]])
top5_shap = set([FEATURE_COLUMNS[i] for i in shap_sorted[:5]])
agree_all = top5_gain & top5_perm & top5_shap
print(f"  Top 5 (Gain):        {[FEATURE_COLUMNS[i] for i in sorted_idx[:5]]}")
print(f"  Top 5 (Permutation): {[FEATURE_COLUMNS[i] for i in perm_sorted[:5]]}")
print(f"  Top 5 (SHAP):        {[FEATURE_COLUMNS[i] for i in shap_sorted[:5]]}")
print(f"  Agreement (all 3):   {agree_all}")
print(f"  Overlap score:       {len(agree_all)}/5 features agree across all methods")

if len(agree_all) >= 3:
    print("  ✅ Strong agreement — model is learning genuine patterns")
else:
    print("  ⚠️ Low agreement — model may be exploiting artifacts")

# Visualization: Feature Importance Comparison
fig, axes = plt.subplots(1, 3, figsize=(18, 6))

# Gain
axes[0].barh(range(len(FEATURE_COLUMNS)), importances[sorted_idx[::-1]], color='#2196F3')
axes[0].set_yticks(range(len(FEATURE_COLUMNS)))
axes[0].set_yticklabels([FEATURE_COLUMNS[i] for i in sorted_idx[::-1]], fontsize=8)
axes[0].set_title('XGBoost Gain', fontweight='bold')
axes[0].set_xlabel('Importance')

# Permutation
axes[1].barh(range(len(FEATURE_COLUMNS)),
             perm_imp.importances_mean[perm_sorted[::-1]], color='#4CAF50',
             xerr=perm_imp.importances_std[perm_sorted[::-1]], capsize=3)
axes[1].set_yticks(range(len(FEATURE_COLUMNS)))
axes[1].set_yticklabels([FEATURE_COLUMNS[i] for i in perm_sorted[::-1]], fontsize=8)
axes[1].set_title('Permutation Importance', fontweight='bold')
axes[1].set_xlabel('Mean Accuracy Decrease')

# SHAP
axes[2].barh(range(len(FEATURE_COLUMNS)), shap_mean[shap_sorted[::-1]], color='#FF9800')
axes[2].set_yticks(range(len(FEATURE_COLUMNS)))
axes[2].set_yticklabels([FEATURE_COLUMNS[i] for i in shap_sorted[::-1]], fontsize=8)
axes[2].set_title('SHAP Importance', fontweight='bold')
axes[2].set_xlabel('Mean |SHAP Value|')

plt.suptitle('Feature Importance — 3-Method Comparison', fontsize=14, fontweight='bold', y=1.02)
plt.tight_layout()
plt.savefig('stress_6_feature_importance.png', dpi=150, bbox_inches='tight')
plt.show()
print("✅ Saved: stress_6_feature_importance.png")

# SHAP Summary Plot (beeswarm)
print("\n  Generating SHAP beeswarm plot...")
fig, ax = plt.subplots(figsize=(12, 8))
if isinstance(shap_values, list):
    # Multi-class: use class 0 (at_risk) as example
    shap.summary_plot(shap_values[0], X_test, feature_names=FEATURE_COLUMNS, show=False)
else:
    # For 3D shap_values (samples, features, classes), sum over classes (axis=2) before passing to summary_plot
    # This aggregates the SHAP values per feature for each sample.
    shap.summary_plot(shap_values.sum(axis=2), X_test, feature_names=FEATURE_COLUMNS, show=False)
plt.title('SHAP Beeswarm — at_risk class', fontweight='bold') # Title might need adjustment as it's now aggregate
plt.tight_layout()
plt.savefig('stress_6_shap_beeswarm.png', dpi=150, bbox_inches='tight')
plt.show()
print("✅ Saved: stress_6_shap_beeswarm.png")

# %% [markdown]
# ---
# ## FINAL SUMMARY

# %%
print("\n" + "=" * 70)
print("  📋 STRESS TESTING COMPLETE — SUMMARY")
print("=" * 70)

print("""
  Visualizations saved:
    stress_1_cv_folds.png          — Cross-validation fold scores
    stress_2_tournament.png        — Model comparison tournament
    stress_3_optimization.png      — Hyperparameter optimization history
    stress_4_robustness.png        — Perturbation & drift robustness
    stress_5_confusion.png         — Confusion matrix deep-dive
    stress_6_feature_importance.png — Feature importance 3-way comparison
    stress_6_shap_beeswarm.png     — SHAP beeswarm plot

  All 6 tests completed successfully.
""")

print("=" * 70)
print("  ✅ STRESS TESTING SUITE COMPLETE (6 TESTS)")
print("=" * 70)