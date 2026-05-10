Dietitian Office Management System

An end-to-end web suite designed for professional dietitian practices. This project was developed as an undergraduate thesis in Computer Science, focusing on high-performance, framework-less PHP development and asynchronous system architecture.

🌟 Key Features
Comprehensive CRM: Full client lifecycle management, from initial onboarding to clinical history tracking.

Hybrid Multilanguage Engine: Robust Greek and English localization implemented at both the application and database schema levels.

Asynchronous Interface: A high-speed UI built on AJAX, ensuring seamless transitions without page reloads.

🛠 Technical Stack
Backend: PHP 8.4 (Vanilla) – Built without heavy frameworks to demonstrate mastery of core language features, OOP principles, and performance optimization.

Database: MySQL 8.0 – Relational schema optimized with strict data integrity.

Frontend: Modern JavaScript (ES6+) & CSS3 – Utilizing a modular "Component-based" approach with pure Vanilla JS.

DevOps: Docker & Docker Compose – Containerized environment for consistent deployment across development and production stages.

PDF Engine: TCPDF – Low-level PDF generation for high-precision reporting.

🏗 System Architecture
The project implements a Modular Component-Based architecture:

SEO Router: A custom-built routing engine via .htaccess that maps clean URLs to internal controllers.

AJAX-First Workflow: 100% of data transactions are handled asynchronously, significantly reducing server load and improving perceived latency.

Layered Security: Integrated authentication guards, CSRF protection, and server-side input validation on every API endpoint.

📦 Installation (Docker)
Clone the repository:

Bash
git clone https://github.com/grmavrikis/thesis.git
Navigate to the project directory:

Bash
cd thesis
Launch the environment:

Bash
docker compose up -d
Access the application:
Open your browser and go to http://localhost:8080.

📄 License
This project was developed for academic purposes as a Bachelor's Thesis. All rights reserved.
