# LIBRARY MANAGEMENT SYSTEM
### Software Engineering-I | Academic Prototype & Working Demo

> **Subtitle:** Smart and Simple Digital Library Management  
> **Course:** Software Engineering-I  
> **Target Environment:** XAMPP (Apache + MySQL + PHP), Modern Web Browsers

---

## 1. Project Overview

The **Library Management System** is a responsive, web-based digital library portal developed for a Software Engineering-I academic demonstration. It enables college students to search the catalogue, check real-time book availability, borrow books, and manage returns. Simultaneously, library administrators can maintain inventory, add/delete book records, monitor all issued loans, and process book returns.

The design adheres to a professional college theme:
- **Navy-Blue & White Palette**: `#0d2346` primary with clean `#f8fafc` backgrounds.
- **Modern Typography & Layout**: Rounded cards, clear badges, accessible tables, and clean forms.
- **Production-Ready Core Reliability**: Robust PHP PDO backend with atomic database transactions preventing over-borrowing and invalid return states.

---

## 2. Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Styling**: Bootstrap 5.3 & Bootstrap Icons
- **Backend**: PHP (Vanilla 7.4 / 8.x, PDO architecture)
- **Database**: MySQL / MariaDB (InnoDB with foreign keys)
- **Local Server Environment**: XAMPP (Apache & MySQL)
- **Code Editor**: Visual Studio Code

---

## 3. Demo Credentials

For academic grading and demonstration, preconfigured demo accounts are seeded into the database:

| Role | Email Address | Password | Access Level |
|---|---|---|---|
| **System Administrator** | `admin@library.com` | `admin123` | Full Admin Portal, Add/Delete Books, Monitor & Return Issued Loans |
| **College Student** | `student@library.com` | `student123` | Student Dashboard, Search Catalogue, Borrow & Return Books, Loan History |

> **Quick Demo Helper**: The login page (`login.php`) includes 1-click **"Fill Student"** and **"Fill Admin"** buttons to autofill credentials instantly during presentations.

---

## 4. Primary Acceptance Test Workflow (13 Steps)

This exact scenario validates the end-to-end functionality required during the evaluation:

1. **Open the Website**: Navigate to `http://localhost/library-management-system/` (or your local XAMPP folder name).
2. **Log in as Student**:
   - Click **Student Login** on the home page or navbar.
   - Enter `student@library.com` / `student123` (or click *Fill Student*).
   - Click **Sign In**.
3. **Search for Book**:
   - In the search bar on the Student Dashboard (or Catalogue), type: `Database System Concepts`.
4. **Verify Initial Availability**:
   - Note the book has **Total Copies: 3** and **Available Copies: 3** with a green **Available (3)** badge.
5. **Borrow the Book**:
   - Click the **Borrow** button next to *Database System Concepts*.
6. **Check Confirmation**:
   - A green confirmation notification appears: *"Book borrowed successfully! ... Due in 14 days."*
7. **Verify Inventory Decrement**:
   - The book's available count automatically decreases from **3 to 2**.
   - The book appears in the student's **"My Borrowed Books"** tab.
8. **Log Out**:
   - Click the user profile dropdown in the top navigation bar and select **Logout**.
9. **Log in as Administrator**:
   - Click **Admin Login**.
   - Enter `admin@library.com` / `admin123` (or click *Fill Admin*).
   - Click **Sign In**.
10. **Open Issued Books Section**:
    - Click on the **"Issued Books"** tab on the Admin Dashboard.
11. **Show Student's Borrowing Transaction**:
    - Locate the transaction for student **Alex Johnson** (`student@library.com`) showing *Database System Concepts* issued today with a 14-day due date.
12. **Return the Book via Admin Portal**:
    - Click the green **"Return Book"** button on this borrowing record.
13. **Verify Restored Inventory**:
    - A confirmation notification appears: *"Book returned successfully! ... is now back in library inventory."*
    - The transaction moves to the **"All Transactions"** history log with status **Returned**.
    - In the **Book Inventory** tab, *Database System Concepts* availability has returned from **2 back to 3**.

---

## 5. XAMPP Setup & Installation Instructions

Follow these step-by-step instructions to run the application on your computer:

### Step 1: Start XAMPP Control Panel
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Ensure both services show green status indicators.

### Step 2: Deploy Project to `htdocs`
1. Copy or move this project folder into your XAMPP web root directory:
   ```
   C:\xampp\htdocs\library-management-system\
   ```
   *(Ensure files like `index.php` are directly inside `C:\xampp\htdocs\library-management-system\`)*

### Step 3: Create Database & Import SQL
1. Open your web browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on **New** in the left sidebar to create a database.
3. In the "Database name" input, enter:
   ```
   library_db
   ```
4. Choose `utf8mb4_unicode_ci` and click **Create**.
5. With `library_db` selected, click on the **Import** tab in the top navigation bar.
6. Click **Choose File** / **Browse...** and select:
   ```
   C:\xampp\htdocs\library-management-system\database\library.sql
   ```
7. Scroll to the bottom and click **Import** (or **Go**).
8. Verify that the tables `users`, `books`, and `transactions` have been created and populated with sample records.

### Step 4: Launch the Application
1. Open your browser and visit:
   ```
   http://localhost/library-management-system/
   ```
2. The landing page will load with live statistics from the MySQL database!

---

## 6. Project Structure

```text
library-management-system/
├── index.php                 # Landing page with welcome message, stats, and role navigation
├── login.php                 # Dual student/admin authentication with 1-click demo filler
├── register.php              # Student registration with validation and password hashing
├── logout.php                # Session termination and flash message redirection
├── student_dashboard.php     # Student portal: search, live availability, borrow & return tabs
├── admin_dashboard.php       # Admin portal: metrics, inventory CRUD, issued books & audit log
├── catalogue.php             # Dedicated full-width searchable library catalogue table
├── borrow.php                # Backend controller for book borrowing with inventory check
├── return.php                # Backend controller for book returning (used by student & admin)
├── add_book.php              # Admin controller & form to add new books to catalogue
├── delete_book.php           # Admin controller to safely delete books (with loan safety check)
├── config/
│   └── database.php          # PDO database connection with graceful troubleshooting UI
├── includes/
│   ├── auth.php              # Session helpers, RBAC access control & flash message engine
│   ├── header.php            # Reusable Bootstrap 5 navbar and page header
│   └── footer.php            # Reusable page footer with links and scripts
├── assets/
│   ├── css/
│   │   └── style.css         # Custom college theme styles, cards, tables, badges
│   └── js/
│       └── script.js         # Client-side live search filter, demo autofill & delete alerts
├── database/
│   └── library.sql           # Schema definition & 8 academic sample books + demo users
└── README.md                 # Complete documentation and setup manual
```

---

## 7. Database Schema Details

### 1. `users` Table
Stores account details for students and administrators:
- `user_id` (INT, Primary Key, Auto Increment)
- `name` (VARCHAR 100)
- `email` (VARCHAR 100, Unique)
- `password` (VARCHAR 255, Bcrypt hashed)
- `role` (ENUM: `'student'`, `'admin'`)
- `created_at` (TIMESTAMP)

### 2. `books` Table
Tracks library titles and physical copy quantities:
- `book_id` (INT, Primary Key)
- `title` (VARCHAR 255)
- `author` (VARCHAR 255)
- `category` (VARCHAR 100)
- `quantity` (INT, Total stock)
- `available_quantity` (INT, Shelf stock available for borrowing)
- `created_at` (TIMESTAMP)

### 3. `transactions` Table
Maintains borrowing audit history:
- `transaction_id` (INT, Primary Key, Auto Increment)
- `user_id` (INT, Foreign Key referencing `users.user_id`)
- `book_id` (INT, Foreign Key referencing `books.book_id`)
- `issue_date` (DATE)
- `due_date` (DATE, default 14 days after issue)
- `return_date` (DATE, NULL until returned)
- `status` (ENUM: `'issued'`, `'returned'`)
- `created_at` (TIMESTAMP)

---

## 8. Sample Book Records Included

| Book ID | Title | Author | Category | Total Quantity | Initial Available |
|---|---|---|---|---|---|
| **101** | Database System Concepts | Abraham Silberschatz | DBMS | 3 | 3 |
| **102** | Operating System Concepts | Abraham Silberschatz | OS | 2 | 2 |
| **103** | Clean Code | Robert C. Martin | Programming | 2 | 2 |
| **104** | Python Crash Course | Eric Matthes | Programming | 4 | 4 |
| **105** | Introduction to Algorithms | Thomas H. Cormen | Algorithms | 3 | 3 |
| **106** | Computer Networks | Andrew S. Tanenbaum | Networking | 2 | 2 |
| **107** | Artificial Intelligence | Stuart Russell | AI | 3 | 3 |
| **108** | The Pragmatic Programmer | Andrew Hunt | Programming | 2 | 2 |

---

## 9. Core Business Rules Implemented

1. **Borrow Rule**: When a book is borrowed, its `available_quantity` in `books` decreases by 1.
2. **Zero-Stock Protection**: If `available_quantity` reaches 0, the book cannot be borrowed, and the system displays *"Book is currently unavailable"*.
3. **Duplicate Prevention**: A student cannot borrow another copy of the same book while they already have an active borrowed copy.
4. **Return Rule**: When a book is returned (either by the student or the administrator), its `available_quantity` in `books` increases by 1 (capped at `quantity`), and the transaction status changes to `'returned'` with the current date recorded as `return_date`.
5. **Deletion Safety**: Administrators cannot delete a book from the catalog while active copies are currently issued to students.
6. **Authentication Security**: Passwords are securely hashed with `password_hash()` (Bcrypt). Standard PHP session management protects restricted routes.

---

## 10. Academic Integrity & Support

This project was built strictly using standard web technologies (**HTML5, CSS3, JavaScript, PHP, MySQL**) without third-party frameworks like React or Node.js, fulfilling all constraints of the Software Engineering-I course specifications.
