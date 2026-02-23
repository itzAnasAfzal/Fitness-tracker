

# Fitness Tracker - Project Documentation

## Project Overview
The Personal Fitness Tracker is a web-based application designed to help users monitor their physical activities, nutrition, and workout routines. It provides a collaborative environment where users can receive feedback and guidance from professional trainers.

### Key Features
- **User Authentication:** Secure login and registration with role-based access control (User, Trainer, Admin).
- **Activity Logging:** Users can log workouts, meals, and water intake.
- **Progress Tracking:** Interactive charts visualize user progress over time (e.g., calories burned, water intake).
- **Workout Routines:** Trainers can post and manage workout plans for users.
- **Nutritional Advice:** A repository of health and nutrition tips categorized for easy access.
- **Feedback System:** Users can provide feedback on routines, and trainers can respond directly.
- **Admin Dashboard:** Centralized management of users, trainers, and system-wide activities.

## Technology Stack
- **Frontend:**
  - **HTML5 & CSS3:** For structure and a premium, responsive design.
  - **Vanilla JavaScript:** Handles client-side logic, password toggling, and dynamic content loading.
  - **Chart.js:** Powering the interactive progress visualizations.
- **Backend:**
  - **PHP 8.x:** The core server-side language managing logic and database interaction.
  - **PDO (PHP Data Objects):** Ensuring secure and flexible database queries.
- **Database:**
  - **MySQL:** Relational database for storing user data, logs, and content.
- **Server Environment:**
  - Designed to run on Apache (e.g., via XAMPP).

## Database Architecture
The system uses a relational schema with the following core entities:
- `users`: Stores profiles for all account types (Admin, Trainer, User).
- `activity_logs`: Records daily metrics like workouts and meals.
- `routines`: Contains workout plans created by trainers.
- `routine_feedback`: Stores user comments on specific routines.
- `trainer_replies`: Connects trainer responses to user feedback.
- `tips`: General fitness advice.
- `nutrition`: Specialized nutritional content.

## Design Philosophy
The application follows a **"Glassmorphism" inspired modern aesthetic**:
- **Centralized CSS:** All styling is managed in `style.css` using modern CSS variables for theme consistency.
- **Responsive Layout:** Uses Flexbox and Grid to ensure a seamless experience across devices.
- **Interactive UI:** Smooth transitions, badge-based categories, and intuitive form layouts.

## Sorting & Data Display
To ensure the most relevant information is readily available, all data (Tips, Routines, Nutrition, Activities, and Feedback) is sorted in **Descending Order (Latest First)** throughout the application.

---
*Created as part of the Fitness Tracker Enhancement Project.*
