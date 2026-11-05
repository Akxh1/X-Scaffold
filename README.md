<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:1a1a2e,50:16213e,100:0f3460&height=220&section=header&text=X-Scaffold&fontSize=72&fontColor=e94560&fontAlignY=35&desc=Predict%20%C2%B7%20Explain%20%C2%B7%20Act&descSize=20&descAlignY=55&descColor=a7c5eb&animation=fadeIn" width="100%" />

<br/>

**A unified framework that synthesises Machine Learning prediction, Explainable AI diagnostics, and LLM-driven adaptive intervention for intelligent learning management.**

<br/>

<p>
<a href="https://skillicons.dev">
<img src="https://skillicons.dev/icons?i=laravel,php,python,flask,tailwind,alpinejs,sqlite,vite,nodejs&perline=9" />
</a>
</p>

[![XGBoost](https://img.shields.io/badge/XGBoost-Classifier-017CEE?style=for-the-badge)](https://xgboost.readthedocs.io)
[![SHAP](https://img.shields.io/badge/SHAP-Explainability-FF6F00?style=for-the-badge)](https://shap.readthedocs.io)
[![Gemini](https://img.shields.io/badge/Gemini-LLM_API-8E75B2?style=for-the-badge&logo=google&logoColor=white)](https://ai.google.dev)

</div>

<br/>

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Project Structure](#project-structure)
- [ML Pipeline](#ml-pipeline)
- [Environment Variables](#environment-variables)
- [API Endpoints](#api-endpoints)
- [Acknowledgements](#acknowledgements)

---

## Overview

Modern Learning Management Systems generate large volumes of student interaction data, yet most platforms stop at surface-level reporting. They can tell an instructor *that* a student is underperforming, but rarely explain *why*, and almost never take proactive steps to help.

X-Scaffold addresses this gap through a unified **Predict-Explain-Act** pipeline:

| Stage | Component | What It Does |
|:-----:|-----------|-------------|
| **Predict** | XGBoost Classifier | Analyses 11 behavioural features captured during exams to classify each student into one of four mastery levels |
| **Explain** | SHAP (XAI Layer) | Generates human-readable factor breakdowns so instructors understand the reasoning behind each prediction |
| **Act** | Gemini LLM | Delivers personalised, level-appropriate hints to students and produces strategic insights for instructors |

### Research Demo

<div align="center">
  <a href="https://youtu.be/6D69IDHaoa8" target="_blank" rel="noopener noreferrer">
    <img src="https://img.youtube.com/vi/6D69IDHaoa8/maxresdefault.jpg" alt="X-Scaffold Research Demo Video" width="80%" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);" />
  </a>
  <p><em>Click the preview above to watch the research demo on YouTube.</em></p>
</div>

---

## Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                    FRONTEND  (Blade + Tailwind)                   │
│   Student Dashboard  ·  Teacher Dashboard  ·  Exam Interface     │
└────────────────────────────┬─────────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────────┐
│                  LARAVEL 12 BACKEND  (PHP 8.2+)                  │
│   Controllers  ·  Services  ·  Middleware  ·  Eloquent ORM       │
├──────────────┬───────────────────────┬───────────────────────────┤
│              │                       │                           │
│  ┌───────────▼──────────┐  ┌────────▼────────┐  ┌──────────────┐│
│  │ MLPredictionService  │  │ GeminiInsights  │  │HintController ││
│  │  (Flask API client)  │  │ Service (LLM)   │  │ (LLM hints)  ││
│  └───────────┬──────────┘  └────────┬────────┘  └──────┬───────┘│
└──────────────┼──────────────────────┼──────────────────┼────────┘
               │                      │                  │
┌──────────────▼──────────┐  ┌────────▼──────────────────▼────────┐
│  FLASK ML API  (Python) │  │      GEMINI API  (Google AI)       │
│  XGBoost + SHAP engine  │  │   gemini-2.5-flash-lite model      │
└─────────────────────────┘  └────────────────────────────────────┘
```

---

## Key Features

### Student Experience

| Feature | Description |
|---------|-------------|
| **Level Indicator Exam** | A diagnostic assessment that captures behavioural data and feeds it into the ML model, producing a personalised mastery classification |
| **Mock Exams** | Unlimited practice exams with AI-powered adaptive hints that respond to each student's predicted level |
| **4-Tier Adaptive Hints** | Hint depth and detail scale dynamically based on the student's mastery level (see table below) |
| **Student Dashboard** | Provides a module-by-module overview of performance metrics, mastery scores, and instructor notifications |
| **Fallback Hints** | When the LLM is unavailable, the system falls back to rule-based, topic-specific hints to ensure uninterrupted learning |

**Adaptive Hint Tiers:**

| Tier | Mastery Level | Scaffolding Strategy |
|:----:|:-------------:|---------------------|
| L1 | Advanced | A single Socratic question designed to push deeper thinking |
| L2 | Proficient | Two guiding bullet points nudging towards the correct approach |
| L3 | Developing | Three numbered steps: recall the concept, avoid a common mistake, take the first action |
| L4 | At Risk | Full concept explanation with a worked example and the correct answer |

### Instructor Experience

| Feature | Description |
|---------|-------------|
| **Instructor Dashboard** | A class-wide overview displaying risk distribution charts, performance analytics, and per-student drill-down capability |
| **AI-Generated Insights** | On-demand Gemini-powered analysis that produces executive summaries, identifies performance patterns, and recommends strategic interventions |
| **Warning System** | Enables instructors to send targeted notifications directly to students identified as struggling |
| **Question Management** | Full CRUD operations with support for Excel/CSV bulk import of exam questions |
| **Data Export** | Export all pipeline data as CSV for offline analysis or ML model retraining |
| **XAI Explanations** | SHAP-based factor breakdowns that show instructors exactly which behavioural indicators contributed to a student's classification |

### ML and Data Pipeline

| Capability | Detail |
|------------|--------|
| **11 Behavioural Features** | Captured from live exam interactions including score, time, confidence, hint usage, tab switches, and more |
| **Learning Mastery Score** | A research-backed composite metric combining six core features into a single mastery indicator |
| **XGBoost Classifier** | Performs 4-class classification: Advanced, Proficient, Developing, and At Risk |
| **SHAP Integration** | Computes per-student feature importance values to power the Explain stage |
| **Synthetic Data Generation** | Uses Cholesky decomposition to generate statistically faithful training data that preserves inter-feature correlations |

---

## Tech Stack

<div align="center">

<table>
<tr>
<td align="center" width="33%">

**Backend**

<a href="https://skillicons.dev">
<img src="https://skillicons.dev/icons?i=laravel,php,python,flask,sqlite&perline=5&theme=dark" />
</a>

Laravel 12 · PHP 8.2+
Python 3.9+ · Flask 3.0

</td>
<td align="center" width="33%">

**Frontend**

<a href="https://skillicons.dev">
<img src="https://skillicons.dev/icons?i=tailwind,alpinejs,vite,nodejs&perline=4&theme=dark" />
</a>

Blade Templates · Tailwind CSS
Alpine.js · Vite 6

</td>
<td align="center" width="33%">

**ML and AI**

<a href="https://skillicons.dev">
<img src="https://skillicons.dev/icons?i=sklearn&perline=3&theme=dark" />
</a>

XGBoost · SHAP · scikit-learn
Google Gemini API

</td>
</tr>
</table>

</div>

---

## Prerequisites

Before setting up the project, ensure the following are installed:

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.2+ | SQLite extension must be enabled |
| Composer | 2.x | PHP dependency manager |
| Node.js | 20+ | Includes npm |
| Python | 3.9+ | With pip |
| Laravel Herd | Latest | Recommended local environment (or any alternative) |
| Gemini API Key | Free tier | Obtain from [Google AI Studio](https://aistudio.google.com/apikey) |

---

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Akxh1/X-Scaffold.git
cd X-Scaffold
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Install Python ML Dependencies

```bash
cd ml_model
pip install -r requirements.txt
cd ..
```

### 5. Configure the Environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and configure the following values:

```env
APP_NAME=X-Scaffold
APP_URL=http://meeple.test       # adjust to your local setup

GEMINI_INSIGHTS_API=your_gemini_api_key_here
```

### 6. Set Up the Database

```bash
php artisan migrate
php artisan db:seed --class=ModulesTableSeeder
php artisan db:seed --class=QuestionsSeeder
php artisan db:seed --class=TeacherUserSeeder
```

---

## Running the Application

### Option A: Concurrent Start (Recommended)

```bash
npm run dev:full
```

This launches both the **Vite** frontend dev server and the **Flask ML API** simultaneously.

In a separate terminal, start the Laravel backend:

```bash
php artisan serve
```

> If you are using **Laravel Herd**, the application is served automatically at your configured `.test` domain.

### Option B: Start Each Service Individually

```bash
# Terminal 1: Laravel server (skip if using Herd)
php artisan serve

# Terminal 2: Vite dev server
npm run dev

# Terminal 3: Flask ML API
cd ml_model
python api.py
```

The ML API will be available at `http://127.0.0.1:5000`.

---

## Project Structure

```
X-Scaffold/
│
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardController.php          # Teacher and student dashboards
│   │   ├── HintController.php               # Adaptive LLM hint generation
│   │   ├── LevelIndicatorExamController.php # Diagnostic exam logic
│   │   ├── MockExamController.php           # Practice exam logic
│   │   ├── NotificationController.php       # Warning and notification system
│   │   └── Teacher/                         # Question management endpoints
│   ├── Models/
│   │   ├── Student.php
│   │   ├── StudentModulePerformance.php     # ML predictions and XAI data
│   │   ├── LevelIndicatorAttempt.php        # Exam attempt with behavioural data
│   │   └── MockExamAttempt.php
│   └── Services/
│       ├── MLPredictionService.php           # Flask API client and LMS engine
│       └── GeminiInsightsService.php         # Gemini AI insights for instructors
│
├── ml_model/
│   ├── api.py                               # Flask REST API (predict + SHAP)
│   ├── train_model.py                       # XGBoost training pipeline
│   ├── predict.py                           # Standalone prediction script
│   ├── generate_dataset.py                  # Cholesky synthetic data generation
│   ├── setup_ml.py                          # One-click ML environment setup
│   ├── xscaffold_xgboost_model.pkl          # Pre-trained model artifact
│   ├── xscaffold_scaler.pkl                 # Feature scaler artifact
│   └── requirements.txt                     # Python dependencies
│
├── resources/views/
│   ├── dashboard.blade.php                  # Teacher dashboard view
│   ├── dashboard/student.blade.php          # Student dashboard view
│   ├── level-indicator/                     # Level Indicator exam views
│   ├── mock-exam/                           # Mock exam views
│   └── welcome.blade.php                    # Landing page
│
├── database/
│   ├── migrations/                          # Schema definitions
│   └── seeders/                             # Sample data seeders
│
└── routes/web.php                           # Application route definitions
```

---

## ML Pipeline

### Learning Mastery Score (LMS)

The Learning Mastery Score is a composite metric designed to capture a more holistic view of student understanding than raw exam scores alone. It is computed as follows:

```
LMS = 0.50 x Score  +  0.15 x HardQAccuracy  +  10 x ConfidenceCalibration
    + 10 x AnswerStability  +  10 x AttentionFocus  -  15 x HintDependency^1.5
```

The formula rewards independent performance, well-calibrated confidence, and sustained attention, while applying a non-linear penalty for excessive hint dependency.

### Classification Levels

| Level | LMS Range | Hint Tier | Interpretation |
|:-----:|:---------:|:---------:|----------------|
| Advanced | 76 to 100 | L1 (Socratic) | Demonstrates independent mastery with minimal scaffolding needed |
| Proficient | 56 to 75 | L2 (Guiding) | Solid foundational understanding with occasional support |
| Developing | 36 to 55 | L3 (Structured) | Requires step-by-step guidance to build confidence |
| At Risk | 0 to 35 | L4 (Full Support) | Needs comprehensive explanation and direct answers |

### 11 Behavioural Features

These features are captured automatically from student interactions during exams:

| Feature | Description |
|---------|-------------|
| `score_percentage` | Overall exam score (0 to 100%) |
| `hard_question_accuracy` | Accuracy on questions marked as difficult |
| `hint_usage_percentage` | Proportion of questions where hints were requested |
| `avg_confidence` | Self-reported confidence rating (1 to 5 scale) |
| `answer_changes_rate` | Number of answer changes per question |
| `tab_switches_rate` | Number of tab switches per question |
| `avg_time_per_question` | Average time spent per question in seconds |
| `review_percentage` | Proportion of questions marked for review |
| `avg_first_action_latency` | Time in seconds before the first interaction on each question |
| `clicks_per_question` | Total mouse clicks per question |
| `performance_trend` | Score difference between the first and second halves of the exam |

### Training the Model

To retrain the model from scratch:

```bash
cd ml_model
python train_model.py
```

This executes the full training pipeline, outputs evaluation metrics, and saves the model artifacts (`xscaffold_xgboost_model.pkl` and `xscaffold_scaler.pkl`).

---

## Environment Variables

| Variable | Required | Description |
|----------|:--------:|-------------|
| `APP_NAME` | Yes | Application display name (default: `X-Scaffold`) |
| `APP_URL` | Yes | Base URL for the application |
| `DB_CONNECTION` | Yes | Database driver (default: `sqlite`) |
| `GEMINI_INSIGHTS_API` | Yes | Google Gemini API key used for hint generation and instructor insights |
| `IPINFO_TOKEN` | No | Optional IPInfo token for geolocation features |

---

## API Endpoints

### Student Routes

*Requires authentication with the `student` role.*

| Method | Route | Description |
|:------:|-------|-------------|
| `GET` | `/dashboard/student` | Student dashboard |
| `GET` | `/module/{module}` | Module detail page |
| `GET` | `/module/{module}/level-indicator/start` | Begin a Level Indicator exam |
| `POST` | `/module/{module}/level-indicator/submit` | Submit a Level Indicator exam |
| `GET` | `/module/{module}/mock-exam/start` | Begin a Mock exam |
| `POST` | `/module/{module}/mock-exam/submit` | Submit a Mock exam |

### Instructor Routes

*Requires authentication with the `teacher` role.*

| Method | Route | Description |
|:------:|-------|-------------|
| `GET` | `/dashboard` | Instructor dashboard |
| `GET` | `/dashboard/student/{student}` | Student detail view with XAI breakdown |
| `POST` | `/dashboard/student/{student}/generate-insights` | Generate AI-powered performance insights |
| `POST` | `/dashboard/student/{student}/warn` | Send a warning notification to a student |
| `GET` | `/dashboard/export-data` | Export pipeline data as CSV |

### Public Routes

| Method | Route | Description |
|:------:|-------|-------------|
| `POST` | `/generate-hint` | Generate an adaptive hint via the LLM |
| `GET` | `/Test-Exam` | Access the standalone test exam interface |

---

## Acknowledgements

This project was developed as part of a final-year dissertation at the University of Westminster. It explores the intersection of educational technology, machine learning, and explainable AI with the aim of creating more responsive and supportive digital learning environments.

**Research Title:**  
*"A Predict-Explain-Act Framework for the LMS: Synthesising Machine Learning Prediction, XAI Diagnostics, and LLM-Driven Adaptive Intervention"*

---

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:1a1a2e,50:16213e,100:0f3460&height=120&section=footer" width="100%" />

</div>
