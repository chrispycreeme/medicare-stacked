# Medicare-v2 🩺✨

Welcome to **Medicare-v2** — an AI-powered doctor’s dashboard for patient monitoring, vital tracking, and real-time health recommendations. This project leverages modern PHP, JavaScript, and Google Gemini AI to deliver actionable insights and a beautiful, responsive UI for healthcare professionals.

---

## 🚀 Features

- **Patient Dashboard**: View patient details, avatars, and vital summaries at a glance.
- **Vitals Visualization**: Interactive charts for heart rate, oxygen level, and body temperature, powered by Chart.js.
- **AI Recommendations**: Real-time, context-aware health advice and risk assessment using Google Gemini 2.0.
- **Dynamic Pagination & Search**: Effortlessly browse and filter patient records.
- **Responsive Design**: Optimized for desktop and mobile, with a modern, accessible interface.
- **Secure Authentication**: Session-based login for doctors, with password hashing utilities.

---

## 🖥️ Project Structure

```
Medicare-v2/
│
├── css/                   # All custom stylesheets
│   ├── dashboard.css
│   ├── vital-grid-cards.css
│   ├── vital-graph.css
│   ├── sidebar-recommendations.css
│   └── styles.css
│
├── js/                    # Frontend logic
│   ├── ai_recommendation_handler.js
│   ├── chart_handler.js
│   └── pagination_handler.js
│
├── php-helper/            # Utility PHP scripts (e.g., password hasher)
│   └── hasher.php
│
├── dashboard.php          # Main dashboard page
├── generate_recommendations.php # AI backend endpoint
└── ...
```

---

## 🧑‍⚕️ How It Works

### 1. **Dashboard Overview**

- **dashboard.php** is the entry point after login.
- Displays patient info, latest vitals, and a sidebar with AI-powered recommendations.
- Uses PHP to inject patient data and session info into the page.

### 2. **Vitals Chart**

- **js/chart_handler.js** initializes a Chart.js line graph.
- Data (`chartLabels`, `heartRateData`, etc.) is passed from PHP to JS.
- Custom gradients and tooltips enhance readability.
- Responsive and interactive for quick trend analysis.

### 3. **AI Recommendations**

- **js/ai_recommendation_handler.js** fetches recommendations from the backend.
- On page load or refresh, it sends patient data to **generate_recommendations.php** via AJAX.
- The backend script:
  - Validates session and input.
  - Crafts a detailed prompt for Google Gemini, requesting a JSON-only response.
  - Parses and returns the AI’s structured advice (overview, risks, precautions).
- The frontend displays this advice in a styled sidebar, with loading states and error handling.

### 4. **Patient Search & Pagination**

- **js/pagination_handler.js** manages patient list navigation.
- Dynamic controls with ellipsis for large datasets.
- Smooth, accessible navigation for doctors with many patients.

---

## 🤖 AI Integration (Google Gemini)

- **generate_recommendations.php** connects directly to Google Gemini 2.0 via API.
- Sends a prompt with patient name, age, latest vitals, and recent history.
- Expects a strict JSON response (no markdown, no extra text).
- Parses and validates the AI output before sending it to the frontend.

**Prompt Example:**
```json
{
  "overview": "Patient is stable. Minor heart rate elevation noted.",
  "health_risks": [
    { "title": "Elevated Heart Rate", "description": "Monitor for stress or exertion." }
  ],
  "precautionary_measures": [
    { "title": "Hydration", "description": "Encourage regular water intake." }
  ]
}
```

---

## 🛡️ Security

- Session-based authentication for all sensitive endpoints.
- Passwords are hashed using PHP’s built-in functions.
- Error logs are written to `php_error.log` (not displayed to users).

---

## 📦 Dependencies

- [Chart.js](https://www.chartjs.org/) for data visualization
- [Google Gemini API](https://ai.google.dev/) for AI recommendations
- [Material Symbols](https://fonts.google.com/icons) for icons
- PHP 7.4+ (with cURL enabled or not.)

**Medicare-v2** — _Empowering doctors with data and AI, one patient at a time._
