
# 📘 LeanWay – AI Smart E-Learning Platform

## 📌 Overview

LeanWay is an AI-powered e-learning platform developed as part of an academic project at **Esprit School of Engineering (2025–2026)**.

The platform is built using the **MVC architecture (Symfony full-stack)** and integrates artificial intelligence to improve learning, student engagement, and academic management.

It provides smart tools such as chatbot assistance, automated quizzes, course summarization, and predictive analytics.

---

## 📑 Table of Contents

* Overview
* Architecture
* Features
* AI Integration
* Screenshots
* Tech Stack
* Installation
* Usage
* Contribution
* License

---

## 🏗️ Architecture (MVC – Symfony)

LeanWay follows the **MVC design pattern**:

* **Model**: Doctrine entities (Users, Courses, Forums, Quizzes, etc.)
* **View**: Twig templates (UI pages)
* **Controller**: Business logic and request handling

✔ Benefits:

* Clean code structure
* Easy maintenance
* Scalability
* Separation of concerns

---

## ⚙️ Main Features

### 📚 Course Management

* Create, update, delete courses
* Student assignment submission
* Grade management system

---

### ⚠️ Complaint & Notification System

* AI-based complaint prioritization
* Automatic email notifications
* Smart response suggestions

---

### 💬 Forum System

* Posts and comments
* Nested replies
* Rating system
* AI-generated reply suggestions

---

### 🔐 Authentication & Security

* User registration and login
* Email verification
* Password recovery
* reCAPTCHA security
* Facial recognition login (admin)

---

### 🤖 AI Intelligence System

* Automatic report generation
* User growth prediction
* Smart recommendations

---

### 📝 AI Quiz Generation

* Automatic quiz creation
* Dynamic questions based on course content

---

### 🤖 AI Chatbot

* Virtual assistant for students
* Academic Q&A support
* 24/7 availability

---

### 📄 AI Course Summarization

* Automatic summaries
* Key concept extraction

---

## 🤖 AI Integration

LeanWay integrates multiple AI technologies:

* Natural Language Processing (NLP)
* Recommendation system
* Predictive analytics
* Computer vision (face recognition)
* Automatic content generation

The face recognition system is powered by a **Python Flask service** connected to the Symfony backend.

---

## 📸 Screenshots

### 🔐 Login Page

![Login](https://raw.githubusercontent.com/MeriemZroud/e_learning-/main/public/login.png)

---

### 📚 Courses Page

![Courses](https://raw.githubusercontent.com/MeriemZroud/e_learning-/main/public/cours.png)

---

### 📝 AI Quiz Generation

![Quiz](https://raw.githubusercontent.com/MeriemZroud/e_learning-/main/public/quiz.png)

---



---

### 📊 Admin Dashboard

![Admin Dashboard](https://raw.githubusercontent.com/MeriemZroud/e_learning-/main/public/admin.png)

---

### 📄 AI Course Summary

![AI Summary](https://raw.githubusercontent.com/MeriemZroud/e_learning-/main/public/resume.png)

---

## 💻 Tech Stack

* Backend & Frontend: Symfony (Full Stack MVC)
* Database: MySQL
* ORM: Doctrine
* Templates: Twig
* AI Services: Python (Flask, OpenCV, face recognition)

---

## ⚙️ Installation

1. Clone the repository
2. Install dependencies using Composer
3. Configure `.env` file (database connection)
4. Run database migrations
5. Start local server

---

## 🚀 Usage

* Students can access courses and quizzes
* Teachers can manage content and evaluations
* Admin can manage users and statistics
* AI chatbot provides assistance
* Face recognition used for secure admin login

---

## 🤝 Contribution

1. Fork the repository
2. Create a new branch
3. Commit changes
4. Push changes
5. Open a Pull Request

---

## 📜 License

This project is developed for academic purposes at **Esprit School of Engineering (2025–2026)**.

---

## 🌟 Key Highlights

* Full-stack Symfony MVC architecture
* AI-powered learning system
* Smart chatbot assistant
* Face recognition authentication
* Predictive analytics dashboard
