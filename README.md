# Edutech LMS

Edutech LMS is a web-based Learning Management System designed to facilitate course management for Admins, Instructors, and Students. This platform allows smooth course creation, enrollment, and tracking of student progress. The system also supports automated quiz generation using lesson titles and Wikipedia API.

## Features

### Admin
- **Dashboard:** Overview of the system.
- **Manage Students:** View, edit, or delete student records.
- **Manage Instructors:** Delete instructors only.
- **Manage Instructor Requests:** Approve or reject instructor registration requests.
- **Manage Courses:** Delete courses only.
- **Manage Course Enrollments:** Delete enrollments if needed.
- **Manage Categories:** Organize courses into categories.

### Instructors
- **Manage Courses:** Create, update, or delete courses.
- **Manage Modules:** Organize course modules.
- **Manage Lessons:** Add lessons inside modules.
- **Manage Materials:** Upload materials inside lessons.
- **Manage Quizzes:** Auto-generate quizzes based on lesson titles using Wikipedia API.

### Students
- **View Courses:** Browse available courses.
- **Dashboard:** Overview of enrolled courses and progress.
- **Profile Management:** Update personal information.
- **Access Lessons & Materials:** View course content after enrollment.
- **Progress Tracking:** Track learning progress.
- **Certificate Generation:** Receive a certificate after course completion.

### Hero Functionality (New)
- **Interactive Cards:** Each card shows course thumbnail, title, instructor, and "Enroll Now" button.
- **Animated Hover Effect:** Cards respond visually on hover for a modern look.
- **Attractive & Easy to Implement:** Minimal backend effort, enhances user experience and visual appeal.

## Technology Stack
- **Backend:** PHP
- **Frontend:** HTML, CSS, Bootstrap
- **Database:** MySQL
- **APIs:** Wikipedia API for quiz generation
- **Other:** FPDF for certificates

## Installation
1. Clone the repository.
2. Import `edutech_lms.sql` into your MySQL database.
3. Update `config.php` with your database credentials.
4. Run the project on a local server (XAMPP, WAMP, etc.).
5. Access the LMS via `http://localhost/edutech-lms/`.

## Usage
- Admin, Instructor, and Student accounts can be created via registration or manually in the database.
- Navigate through the dashboard to explore features.
- Students will see the hero section with recommended courses on their dashboard.

## Future Enhancements
- Add **search functionality** for courses.
- Implement **real-time notifications** for students and instructors.
- Improve **responsive UI** for mobile devices.
- Enhance quiz generation with AI-based suggestions.

---

**Note:** This LMS project demonstrates key features of a functional learning management system, including automated quiz generation, progress tracking, and certificate generation.
