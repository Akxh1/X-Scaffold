# X-Scaffold

> **A Predict-Explain-Act Framework for Intelligent Learning Management**

X-Scaffold is a research-driven web application that unifies **Machine Learning prediction**, **Explainable AI (XAI) diagnostics**, and **LLM-driven adaptive intervention** into a single coherent platform. It predicts student performance risk, explains predictions to instructors in human-understandable terms, and automatically delivers personalised scaffolding to students.

---

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
- [Default Accounts](#default-accounts)
- [API Endpoints](#api-endpoints)
- [License](#license)

---

## Overview

Current Learning Management Systems collect vast amounts of data yet fail to close the loop between **identifying** a struggling student and **helping** them effectively. X-Scaffold addresses this gap through a three-stage pipeline:

| Stage | Component | Role |
|-------|-----------|------|
| **Predict** | XGBoost ML Model | Classifies students into 4 mastery levels using 11 behavioural features |
| **Explain** | SHAP (XAI) | Translates predictions into human-readable factor breakdowns for instructors |
| **Act** | Gemini LLM | Delivers adaptive, level-appropriate hints and generates strategic insights |

---

## Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                      FRONTEND (Blade + Tailwind)                 │
│  Student Dashboard │ Teacher Dashboard │ Exam Interface          │
└────────────────────────────┬─────────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────────┐
│                   LARAVEL 12 BACKEND (PHP 8.2+)                  │
│  Controllers │ Services │ Middleware │ Eloquent ORM              │
├──────────────┬───────────────────────┬───────────────────────────┤
│              │                       │                           │
│  ┌───────────▼──────────┐  ┌────────▼────────┐  ┌──────────────┐│
│  │  MLPredictionService │  │ GeminiInsights  │  │ HintController││
│  │  (Flask API client)  │  │ Service (LLM)   │  │ (LLM hints)  ││
│  └───────────┬──────────┘  └────────┬────────┘  └──────┬───────┘│
└──────────────┼──────────────────────┼──────────────────┼────────┘
               │                      │                  │
┌──────────────▼──────────┐  ┌────────▼──────────────────▼────────┐
│   FLASK ML API (Python) │  │     GEMINI API (Google AI)         │
│  XGBoost + SHAP engine  │  │  gemini-2.5-flash-lite             │
└─────────────────────────┘  └────────────────────────────────────┘
```

---

## Key Features

### For Students
- **Level Indicator Exam** — Diagnostic assessment that feeds the ML model to classify mastery level
- **Mock Exams** — Unlimited practice exams with adaptive AI-powered hints
- **4-Tier Adaptive Hints** — Hint depth scales to mastery level:
  - **L1 (Advanced)** — Socratic question only
  - **L2 (Proficient)** — Guiding bullet points
  - **L3 (Developing)** — Step-by-step numbered walkthrough
  - **L4 (At Risk)** — Full concept explanation with the answer
- **Student Dashboard** — Module performance overview, mastery scores, and notifications
- **Fallback Hints** — Rule-based topic-specific hints when the LLM is unavailable

### For Instructors
- **Instructor Dashboard** — Class overview with risk distribution, performance analytics, and student drill-down
- **AI-Generated Insights** — Gemini-powered executive analysis of each student's performance patterns
- **Warning System** — Send targeted notifications to struggling students
- **Question Management** — Full CRUD + Excel/CSV bulk import for exam questions
- **Data Export** — Export pipeline data as CSV for ML retraining
- **XAI Explanations** — View SHAP-based factor breakdowns showing *why* a student was classified at their level

### ML & Data Pipeline
- **11 Behavioural Features** captured from exam interactions (score, time, confidence, hint usage, tab switches, etc.)
- **Learning Mastery Score (LMS)** — Research-backed composite metric combining 6 core features
- **XGBoost Classifier** — 4-class classification (Advanced, Proficient, Developing, At Risk)
- **SHAP Integration** — Per-student feature importance explanations
- **Synthetic Data Generation** — Cholesky decomposition for training data augmentation

---

## Tech Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.2+ | Server-side runtime |
| Laravel | 12 | Web framework |
| SQLite | — | Database |
| Python | 3.9+ | ML pipeline |
| Flask | 3.0+ | ML API server |

### Frontend
| Technology | Purpose |
|------------|---------|
| Blade Templates | Server-side rendering |
| Tailwind CSS | Utility-first styling |
| Alpine.js | Lightweight JS interactivity |
| Vite | Asset bundling & HMR |

### ML & AI
| Technology | Purpose |
|------------|---------|
| XGBoost | Student performance classification |
| SHAP | Explainable AI feature importance |
| scikit-learn | Preprocessing, metrics, cross-validation |
| Gemini API | LLM-powered hints & instructor insights |

---

## Prerequisites

- **PHP** 8.2+ with SQLite extension
- **Composer** 2.x
- **Node.js** 20+ and npm
- **Python** 3.9+ with pip
- **Laravel Herd** (recommended) or any local PHP development environment
- **Google AI Studio API Key** — [Get one free](https://aistudio.google.com/apikey)

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/Akxh1/meeple.git
cd meeple
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node.js dependencies

```bash
npm install
```

### 4. Install Python ML dependencies

```bash
cd ml_model
pip install -r requirements.txt
cd ..
```

### 5. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and add your API keys:

```env
APP_NAME=X-Scaffold
APP_URL=http://meeple.test    # or http://localhost:8000

GEMINI_INSIGHTS_API=your_gemini_api_key_here
```

### 6. Database setup

```bash
php artisan migrate
php artisan db:seed --class=ModulesTableSeeder
php artisan db:seed --class=QuestionsSeeder
php artisan db:seed --class=TeacherUserSeeder
```

---

## Running the Application

### Option A: All-in-one (recommended)

```bash
npm run dev:full
```

This starts **Vite** (frontend HMR) and the **Flask ML API** concurrently.

In a separate terminal, serve the Laravel app:

```bash
php artisan serve
```

Or if using **Laravel Herd**, the app is automatically served at `http://meeple.test`.

### Option B: Individual services

```bash
# Terminal 1 — Laravel server (skip if using Herd)
php artisan serve

# Terminal 2 — Vite dev server
npm run dev

# Terminal 3 — Flask ML API
cd ml_model
python api.py
```

The ML API runs on `http://127.0.0.1:5000` by default.

---

## Project Structure

```
meeple/
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardController.php     # Teacher & student dashboards
│   │   ├── HintController.php          # Adaptive LLM hint generation
│   │   ├── LevelIndicatorExamController.php  # Diagnostic exam
│   │   ├── MockExamController.php      # Practice exams
│   │   ├── NotificationController.php  # Warning/notification system
│   │   └── Teacher/                    # Question management
│   ├── Models/
│   │   ├── Student.php
│   │   ├── StudentModulePerformance.php  # ML predictions & XAI data
│   │   ├── LevelIndicatorAttempt.php     # Exam attempt with behaviourals
│   │   └── MockExamAttempt.php
│   └── Services/
│       ├── MLPredictionService.php      # Flask API client + LMS calculation
│       └── GeminiInsightsService.php    # Gemini AI insights for teachers
├── ml_model/
│   ├── api.py                          # Flask REST API (predict + SHAP)
│   ├── train_model.py                  # XGBoost training pipeline
│   ├── predict.py                      # Standalone prediction script
│   ├── generate_dataset.py             # Cholesky synthetic data generation
│   ├── setup_ml.py                     # One-click ML setup
│   ├── xscaffold_xgboost_model.pkl     # Trained model
│   ├── xscaffold_scaler.pkl            # Feature scaler
│   └── requirements.txt               # Python dependencies
├── resources/views/
│   ├── dashboard.blade.php             # Teacher dashboard
│   ├── dashboard/student.blade.php     # Student dashboard
│   ├── level-indicator/                # Level Indicator exam views
│   ├── mock-exam/                      # Mock exam views
│   └── welcome.blade.php              # Landing page
├── database/
│   ├── migrations/                     # Schema definitions
│   └── seeders/                        # Sample data seeders
└── routes/web.php                      # All application routes
```

---

## ML Pipeline

### Learning Mastery Score (LMS)

The LMS is a composite metric that goes beyond raw exam scores:

```
LMS = 0.50×Score + 0.15×HardQAccuracy + 10×ConfidenceCalibration
      + 10×AnswerStability + 10×AttentionFocus − 15×HintDependency^1.5
```

### Classification Levels

| Level | LMS Range | Hint Depth | Description |
|-------|-----------|------------|-------------|
| 🟢 Advanced | 76–100 | L1 — Socratic | Independent mastery, minimal scaffolding |
| 🔵 Proficient | 56–75 | L2 — Guiding | Solid understanding, light nudges |
| 🟠 Developing | 36–55 | L3 — Structured | Needs step-by-step support |
| 🔴 At Risk | 0–35 | L4 — Full support | Comprehensive explanation with answers |

### 11 Behavioural Features

| Feature | Description |
|---------|-------------|
| `score_percentage` | Overall exam score (0–100%) |
| `hard_question_accuracy` | Accuracy on difficult questions |
| `hint_usage_percentage` | % of questions where hints were used |
| `avg_confidence` | Self-reported confidence (1–5) |
| `answer_changes_rate` | Answer changes per question |
| `tab_switches_rate` | Tab switches per question |
| `avg_time_per_question` | Average seconds per question |
| `review_percentage` | % of questions marked for review |
| `avg_first_action_latency` | Seconds before first interaction |
| `clicks_per_question` | Total clicks per question |
| `performance_trend` | Score change between first and second half |

### Training the Model

```bash
cd ml_model
python train_model.py
```

This will train the XGBoost classifier, output metrics, and save `xscaffold_xgboost_model.pkl` and `xscaffold_scaler.pkl`.

---

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_NAME` | Yes | Application name (default: `X-Scaffold`) |
| `APP_URL` | Yes | Application URL |
| `DB_CONNECTION` | Yes | Database driver (default: `sqlite`) |
| `GEMINI_INSIGHTS_API` | Yes | Google Gemini API key for hints & insights |
| `IPINFO_TOKEN` | No | IPInfo token for geolocation |

---

## Default Accounts

After running seeders, the following accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Teacher | `teacher@example.com` | `password123` |
| Teacher | `teacher@test.com` | *(same as student)* |
| Student | `student@test.com` | *(set during seeding)* |

---

## API Endpoints

### Student Routes (authenticated, role: student)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/dashboard/student` | Student dashboard |
| GET | `/module/{module}` | Module detail page |
| GET | `/module/{module}/level-indicator/start` | Start Level Indicator exam |
| POST | `/module/{module}/level-indicator/submit` | Submit Level Indicator exam |
| GET | `/module/{module}/mock-exam/start` | Start Mock exam |
| POST | `/module/{module}/mock-exam/submit` | Submit Mock exam |

### Instructor Routes (authenticated, role: teacher)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/dashboard` | Teacher dashboard |
| GET | `/dashboard/student/{student}` | Student detail + XAI breakdown |
| POST | `/dashboard/student/{student}/generate-insights` | Generate AI insights |
| POST | `/dashboard/student/{student}/warn` | Send warning notification |
| GET | `/dashboard/export-data` | Export CSV for ML retraining |

### Public Routes

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/generate-hint` | Generate adaptive hint (LLM) |
| GET | `/Test-Exam` | Test exam interface |

---

## License

This project is developed as part of an academic research project.

MIT License — see [LICENSE](LICENSE) for details.
