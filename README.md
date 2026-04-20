# CampusCare API

Core PHP REST backend for the CampusCare university complaint management system.

## Base URL

If this folder is served directly under `htdocs` as `campuscare-api`, use:

`http://localhost/campuscare-api/`

In the current workspace layout under `D:\xamp\htdocs\campuscare`, the URL is:

`http://localhost/campuscare/campuscare-api/`

## Configuration

Set these environment variables if you do not want the local defaults:

- `CAMPUSCARE_DB_HOST`
- `CAMPUSCARE_DB_NAME`
- `CAMPUSCARE_DB_USER`
- `CAMPUSCARE_DB_PASS`
- `CAMPUSCARE_JWT_SECRET`

Default database connection:

- Host: `127.0.0.1`
- Database: `campuscare`
- User: `root`
- Password: empty

## Setup

1. Import [database/schema.sql](D:\xamp\htdocs\campuscare\campuscare-api\database\schema.sql).
2. Ensure Apache serves the `campuscare-api` directory.
3. Use `Authorization: Bearer <token>` for protected routes.

Seeded admin credentials:

- Email: `admin@campuscare.local`
- Password: `admin123`

## Endpoint Summary

### Auth

- `POST /auth/register.php`
- `POST /auth/login.php`
- `POST /auth/logout.php`

### Student

- `POST /student/createComplaint.php`
- `GET /student/getMyComplaints.php`
- `POST /student/escalateComplaint.php`

### Mentor

- `GET /mentor/getAssignedComplaints.php`
- `POST /mentor/updateComplaintStatus.php`

### Warden

- `GET /warden/getHostelComplaints.php`
- `POST /warden/updateComplaintStatus.php`

### IRO

- `GET /iro/getInternationalComplaints.php`
- `POST /iro/updateComplaintStatus.php`

### Admin

- `POST /admin/createMentor.php`
- `POST /admin/createWarden.php`
- `POST /admin/createIRO.php`
- `GET /admin/getUserOverview.php`
- `GET /admin/getStudents.php`
- `GET /admin/getMentors.php`
- `GET /admin/getWardens.php`
- `GET /admin/getIROOfficers.php`
- `GET /admin/getHostels.php`
- `GET /admin/getAllComplaints.php`
- `POST /admin/updateUser.php`
- `POST /admin/toggleUserStatus.php`
- `POST /admin/reassignAuthority.php`
- `POST /admin/reassignComplaint.php`
- `GET /admin/analytics.php`

## Admin User Management Notes

The admin user management module now expects a `status` column on `users` with values `active` or `disabled`.

If you already imported an older version of the schema, run:

```sql
ALTER TABLE users
ADD COLUMN status ENUM('active', 'disabled') NOT NULL DEFAULT 'active' AFTER hostel_id;
```

Disabled users cannot log in and cannot authenticate on protected endpoints.

## Example Requests

### Register student

`POST /auth/register.php`

```json
{
  "name": "Aarav Singh",
  "email": "aarav@example.com",
  "password": "secret123",
  "role": "national",
  "roll_number": "23CSE101",
  "gender": "male",
  "phone": "9876543210",
  "hostel_id": 1
}
```

```json
{
  "status": "success",
  "message": "Registration successful.",
  "data": {
    "user_id": 7,
    "role": "national",
    "mentor_id": 3,
    "iro_id": null,
    "token": "jwt-token-value"
  }
}
```

### Login

`POST /auth/login.php`

```json
{
  "email": "aarav@example.com",
  "password": "secret123"
}
```

```json
{
  "status": "success",
  "message": "Login successful.",
  "data": {
    "user_id": 7,
    "role": "national",
    "token": "jwt-token-value",
    "user": {
      "id": 7,
      "name": "Aarav Singh",
      "email": "aarav@example.com",
      "role": "national",
      "roll_number": "23CSE101",
      "gender": "male",
      "phone": "9876543210",
      "hostel_id": 1,
      "created_at": "2026-03-11 11:30:00"
    }
  }
}
```

### Create complaint

`POST /student/createComplaint.php`

Headers:

- `Authorization: Bearer <token>`

```json
{
  "category_id": 1,
  "title": "Water supply issue",
  "description": "There has been no water on the second floor since morning."
}
```

```json
{
  "status": "success",
  "message": "Complaint created successfully.",
  "data": {
    "complaint_id": 14,
    "assigned_to": 5,
    "route_to": "warden",
    "status": "submitted"
  }
}
```

### Update complaint status

`POST /mentor/updateComplaintStatus.php`

Headers:

- `Authorization: Bearer <token>`

```json
{
  "complaint_id": 14,
  "status": "in_progress",
  "message": "Meeting scheduled with the student tomorrow."
}
```

### Reassign complaint

`POST /admin/reassignComplaint.php`

```json
{
  "complaint_id": 14,
  "assigned_to": 2,
  "status": "in_progress"
}
```

## React Axios Usage

```js
import axios from "axios";

const api = axios.create({
  baseURL: "http://localhost/campuscare-api/",
});

api.post("/auth/login.php", {
  email: "admin@campuscare.local",
  password: "admin123",
});
```

If you keep the backend in the current workspace path, change `baseURL` to `http://localhost/campuscare/campuscare-api/`.
