# To-Do List Web App

A simple task management application created using pure web technologies (HTML, CSS, JavaScript, PHP, MySQL). Built as a final project for a web technologies course — Variant A (no frameworks).

## 🧾 Features

- User registration and login (with hashed passwords)
- Task management:
  - Add new tasks
  - Edit task title and description
  - Mark tasks as completed
  - Delete tasks
  - Filter by task status
- Individual task dashboards per user
- Secure login and session handling

## 🛠 Technologies Used

- **HTML5**, **CSS3** – structure and styling
- **JavaScript** – client-side validation, interactivity
- **PHP** – server-side logic (no frameworks)
- **MySQL** – data storage
- **XAMPP** – local server environment

## 🗂 Project Structure

```
todo_app
├───dashboard.php
├───delete_task.php
├───edit_task.php
├───index.php
├───logout.php
├───update_task_status.php
│
├───auth
│   ├───login.php
│   ├───register.php
│   └───too_many_attempts.php
│
├───cache
├───css
│   ├───dashboard.css
│   ├───edit-task.css
│   ├───index.css
│   ├───register+login.css
│   └───style.css
│
├───db_queries
│   └───user_tasks.sql
│
├───images
│   ├───bg2.webp
│   └───bg4_space-nebula-3D_3840x2160.webp
│
└───includes
    └───db.php
```

## ⚙️ Installation (XAMPP)

1. Copy the project folder into `htdocs` in your XAMPP directory.
2. Start **Apache** and **MySQL** via XAMPP Control Panel.
3. Open [phpMyAdmin](http://localhost/phpmyadmin) and import `todo_app_db_import.sql`.
4. Visit `http://localhost/todo_app/` in your browser.

## 🛡️ Security

- Passwords are hashed using bcrypt
- Client-side and server-side form validation
- Session-based authentication
- SQL injection protection using prepared statements

## 📊 Database Overview

### Tables

- `users` (id, username, password, is_locked)
- `tasks` (id, user_id, title, description, is_done, created_at)

### View

- `user_tasks` — joins users and tasks by user_id for display purposes


![ERD Diagram - Database](https://github.com/user-attachments/assets/89a35225-9789-491b-ae8c-7cd571cbb0e0)


## 🔐 Use Cases

- A user registers and logs into the system
- User adds, edits, completes, or deletes tasks
- System displays tasks for the logged-in user


![Usecase Diagram](https://github.com/user-attachments/assets/9b853bfd-4191-46a5-85d3-4a71848a0e13)


## 📸 Screenshots

Homepage


![1_Homepage](https://github.com/user-attachments/assets/bafecfed-16cf-4b72-bd4e-5bac2a8a655e)


Login / Registration page

![2_Registration](https://github.com/user-attachments/assets/8de11afa-e1b2-4748-81ee-afcc1b60b283)

![3_Login](https://github.com/user-attachments/assets/260a98a4-159e-4355-a54e-0d534b0d6b0d)

User dashboard

![4_DashBoard](https://github.com/user-attachments/assets/5cfb6750-2a9d-4551-8b1e-97eff348858f)

Task Edition

![5_TaskEdition](https://github.com/user-attachments/assets/48a51a90-576e-4cde-b531-dbbd1c752555)


## 👤 Author

**Baltazar / Bartosz C**

GitHub: [https://github.com/Ba1-Tazar/todo-app](https://github.com/Ba1-Tazar/todo-app)

---

_Project created as part of a university course in web technologies — Variant A (pure technologies)._
