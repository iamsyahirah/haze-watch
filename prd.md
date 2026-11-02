# HazeWatch – Product Requirements Document (PRD)

**Version:** 1.0  
**Project:** HazeWatch  
**Project Type:** Web Application  
**Target Market:** Malaysia  
**Status:** Planned  
**Development Approach:** AI-assisted development using OpenAI Codex

---

# 1. Project Overview

## 1.1 Product Description

HazeWatch is a Malaysian Air Quality Monitoring and Alert System.

The application helps users monitor air quality across different locations in Malaysia, understand pollution trends, and receive notifications when air quality reaches unhealthy levels.

The system retrieves air quality measurements from external APIs, processes and stores the data in MySQL, and presents the information through an interactive dashboard.

HazeWatch is designed as a responsive web application for the general public, students, and people interested in environmental monitoring.

## 1.2 Problem Statement

People may want to know:

- Is the air quality in my area healthy?
- Which locations currently have unhealthy air quality?
- Is air quality getting better or worse?
- What happens when pollution reaches an unhealthy level?
- How has air quality changed over the past few days?

Air quality information can be scattered across different platforms.

HazeWatch aims to provide a centralised platform for monitoring air quality, viewing historical measurements, and receiving location-based alerts.

## 1.3 Project Objectives

1. Display current air quality information for selected Malaysian locations.
2. Retrieve air quality information from external APIs.
3. Store historical air quality measurements in MySQL.
4. Visualise air quality trends using interactive charts.
5. Automatically monitor unhealthy air quality conditions.
6. Allow users to subscribe to location-based alerts.
7. Provide an administrative dashboard for monitoring data collection.

---

# 2. Target Users

| User           | Description                                              |
| -------------- | -------------------------------------------------------- |
| General Public | Monitor air quality in their area.                       |
| Students       | Explore air pollution information and trends.            |
| Researchers    | Review historical air quality measurements.              |
| Administrator  | Manage locations, users, API integration and monitoring. |

---

# 3. Technology Stack

| Component                | Technology            |
| ------------------------ | --------------------- |
| Backend                  | Core PHP 8.2+         |
| Database                 | MySQL 8               |
| Database Access          | PDO                   |
| Frontend                 | HTML, CSS, JavaScript |
| CSS Framework            | Bootstrap 5           |
| Charts                   | Chart.js              |
| API Communication        | PHP cURL              |
| Authentication           | PHP Sessions          |
| Scheduled Tasks          | Cron Jobs             |
| Version Control          | Git and GitHub        |
| AI Development Assistant | OpenAI Codex          |

## 3.1 Development Principles

- Use Core PHP without Laravel.
- Follow modular PHP architecture.
- Use PDO prepared statements.
- Separate business logic from controllers.
- Write reusable services.
- Use environment variables for sensitive configuration.
- Implement server-side validation.
- Follow security best practices.
- Write readable and maintainable code.
- Document important implementation decisions.

---

# 4. Application Features

Features are divided into three development phases.

## PHASE 1: MVP

### F01. Public Air Quality Dashboard

The public dashboard displays an overview of air quality information.

Requirements:

- Display current AQI/IPU when a valid index is available.
- Display PM2.5 measurements when available.
- Display PM10 measurements when available.
- Display air quality categories.
- Display the last updated timestamp.
- Display monitored locations.
- Display locations with the highest available pollution measurements.
- Provide location search.
- Show data source information.

Air quality categories must follow the applicable measurement standard.

Do not calculate or label an unofficial measurement as Malaysia's official IPU.

### F02. Location Monitoring

Users can browse and explore monitored Malaysian locations.

Initial proposed locations:

- Kuala Lumpur
- Shah Alam
- Petaling Jaya
- Johor Bahru
- George Town
- Alor Setar
- Kuantan
- Kuching
- Kota Kinabalu
- Langkawi

Requirements:

- Browse locations by state.
- Search locations by name.
- View individual location details.
- Display current available readings.
- Display historical measurements.
- Display location coordinates when available.
- Show measurement source and timestamp.

Location availability depends on external API coverage.

Do not create fake live readings for unsupported locations.

### F03. Historical Air Quality Charts

Requirements:

- Display historical PM2.5 measurements.
- Display historical PM10 measurements.
- Provide 24-hour, 7-day and 30-day filters.
- Display measurement values and units.
- Show historical trends using Chart.js.
- Display missing-data periods accurately.

### F04. User Authentication

Requirements:

- User registration.
- User login.
- User logout.
- Password hashing.
- Password verification.
- Password reset.
- User profile management.
- Secure session management.

### F05. Favourite Locations

Requirements:

- Add locations to favourites.
- Remove locations from favourites.
- Display favourite locations on the user dashboard.
- Allow quick access to favourite location details.

---

## PHASE 2: Advanced Features

### F06. Automatic Data Collection

This is one of the main backend features.

Requirements:

- Retrieve air quality measurements from external APIs.
- Use PHP cron jobs for scheduled data collection.
- Validate external API responses.
- Normalize measurements into a consistent database structure.
- Store measurements in MySQL.
- Prevent duplicate measurements.
- Record synchronization status.
- Record API errors.
- Retry failed requests with appropriate limits.
- Track the last successful synchronization time.

Suggested collection interval:

Every 30–60 minutes, subject to the provider's caching policy and API rate limits.

### F07. Air Quality Alert System

Users can configure alerts for their favourite locations.

Requirements:

- Create alert rules.
- Configure pollution thresholds.
- Notify users when measurements exceed configured thresholds.
- Notify users when air quality improves, where applicable.
- Display notification history.
- Mark notifications as read.
- Prevent duplicate notifications for the same ongoing alert condition.

MVP notification channel:

- In-app notifications.

Future notification channels:

- Email notifications.
- Browser push notifications.

### F08. Air Quality Analytics

Generate useful information from historical measurements.

Requirements:

- Calculate average PM2.5 per day.
- Calculate average PM10 per day.
- Identify the most polluted monitored location.
- Identify locations with improving air quality.
- Compare selected locations.
- Generate weekly summaries.
- Generate monthly summaries.

All calculations must use valid measurements and appropriate averaging periods.

### F09. Data Quality Monitoring

Requirements:

- Identify stale readings.
- Detect missing measurements.
- Validate measurement values.
- Flag suspicious data.
- Track measurement sources.
- Track measurement timestamps.
- Prevent failed API requests from overwriting valid data.

---

## PHASE 3: Future Enhancements

These features are outside the initial MVP scope.

- Interactive map showing monitoring locations.
- Browser geolocation for nearby monitoring stations.
- Email notifications.
- Browser push notifications.
- Export historical measurements to CSV.
- Public REST API for third-party developers.
- Compare multiple locations.
- Weather information integration.
- AI-powered air quality trend analysis.
- IoT sensor integration.

---

# 5. User Interface Requirements

## 5.1 Public Dashboard

Main components:

- HazeWatch logo and navigation.
- Current air quality overview.
- Location search.
- Air quality summary cards.
- Most polluted monitored locations.
- Historical air quality chart.
- Latest monitoring updates.
- Login and registration links.

## 5.2 Location Details Page

Main components:

- Location name.
- State.
- Current available measurements.
- AQI/IPU category when available.
- PM2.5 measurement.
- PM10 measurement.
- Historical charts.
- Measurement timestamp.
- Data source information.
- Add to favourites button.

## 5.3 User Dashboard

Main components:

- User profile.
- Favourite locations.
- Air quality overview.
- Alert configuration.
- Recent notifications.
- Notification history.

## 5.4 Admin Dashboard

Main components:

- Total monitored locations.
- Total collected measurements.
- Latest successful synchronization.
- Failed API requests.
- API synchronization logs.
- User management.
- Location management.
- Manual data synchronization.
- Data quality overview.

## 5.5 UI Design Guidelines

Design direction:

- Modern environmental monitoring dashboard.
- Clean and professional interface.
- Responsive desktop and mobile layouts.
- Green, blue, orange and red status colours.
- Clear typography.
- Readable charts.
- Consistent spacing.
- Accessible contrast.
- Avoid excessive animations.

---

# 6. Database Design

The initial database will contain 10 main tables.

| Table                | Purpose                                              |
| -------------------- | ---------------------------------------------------- |
| users                | Store user accounts.                                 |
| states               | Store Malaysian states.                              |
| locations            | Store monitoring locations.                          |
| data_sources         | Store external API provider information.             |
| sensors              | Store monitoring sensor information, when available. |
| air_quality_readings | Store historical pollutant measurements.             |
| favourite_locations  | Store user favourite locations.                      |
| alert_rules          | Store user alert configurations.                     |
| notifications        | Store user notifications.                            |
| api_sync_logs        | Store API synchronization logs.                      |

## 6.1 Database Requirements

- Use primary keys.
- Use foreign keys.
- Add appropriate indexes.
- Use DECIMAL for measurement values.
- Store timestamps consistently in UTC.
- Convert timestamps to Asia/Kuala_Lumpur for display.
- Prevent duplicate source measurements.
- Use appropriate database constraints.
- Use database transactions for important operations.

## 6.2 air_quality_readings Table

Suggested columns:

| Column            | Data Type         | Description                 |
| ----------------- | ----------------- | --------------------------- |
| id                | BIGINT            | Primary key                 |
| location_id       | BIGINT            | Location reference          |
| data_source_id    | BIGINT            | API source reference        |
| sensor_id         | BIGINT NULL       | Sensor reference            |
| parameter         | VARCHAR(30)       | PM2.5, PM10, etc.           |
| value             | DECIMAL(10,3)     | Measurement value           |
| unit              | VARCHAR(20)       | Measurement unit            |
| recorded_at       | DATETIME          | Measurement timestamp       |
| fetched_at        | DATETIME          | Retrieval timestamp         |
| source_reading_id | VARCHAR(100) NULL | External reading identifier |

PM2.5 and PM10 should be stored as separate measurement records.

The schema should support additional pollutant parameters in the future.

---

# 7. External API Integration

## 7.1 Proposed API Providers

### Primary MVP Candidate: Haze Monitor API

Documentation:

https://hazemonitor.com/developers

Purpose:

- Retrieve Malaysian air quality measurements.
- Retrieve monitoring station information.
- Retrieve supported pollutant readings.

Requirements:

- Inspect actual API responses.
- Identify supported Malaysian locations.
- Understand available pollutants and units.
- Respect API caching instructions.
- Handle API errors.
- Avoid unnecessary requests.

### Alternative: OpenAQ API

Documentation:

https://docs.openaq.org/

Purpose:

- Retrieve pollutant measurements.
- Explore available monitoring locations.
- Supplement air quality information.

Check Malaysian data coverage and API requirements before implementation.

### Supplementary Historical Data: Malaysia Open Data

Dataset:

https://data.gov.my/data-catalogue/air_pollution

Purpose:

- Supplement historical air pollution analysis.
- Explore official Malaysian air pollution datasets.

The monthly dataset should not be treated as a live monitoring source.

### Official Reference: Department of Environment Malaysia

Website:

https://www.doe.gov.my/

Purpose:

- Understand official Malaysian IPU information.
- Investigate official data access.
- Verify applicable measurement classifications.

## 7.2 API Integration Architecture

External API
|
v
PHP API Client
|
v
Response Validation
|
v
Data Normalization
|
v
Duplicate Detection
|
v
MySQL Database
|
v
HazeWatch Dashboard

## 7.3 API Integration Requirements

Create reusable services:

- ApiClient
- AirQualityService
- DataNormalizer
- DataValidationService
- DataSyncService

Do not put API communication directly inside controllers.

Do not expose private API credentials in frontend JavaScript.

## 7.4 Data Handling Rules

- Preserve original measurement values.
- Preserve original measurement units.
- Preserve measurement timestamps.
- Record the source provider.
- Do not assume different providers use identical measurement standards.
- Do not fabricate missing data.
- Clearly distinguish official IPU from unofficial calculated indicators.

---

# 8. Internal REST API

HazeWatch should provide its own REST API endpoints.

| Method | Endpoint                     | Description                        |
| ------ | ---------------------------- | ---------------------------------- |
| GET    | /api/locations               | List monitored locations           |
| GET    | /api/locations/{id}          | Get location details               |
| GET    | /api/locations/{id}/readings | Get historical measurements        |
| GET    | /api/air-quality/current     | Get current available measurements |
| GET    | /api/air-quality/rankings    | Get location rankings              |
| GET    | /api/analytics/summary       | Get analytics summary              |
| GET    | /api/favourites              | Get user favourites                |
| POST   | /api/favourites              | Add favourite                      |
| DELETE | /api/favourites/{id}         | Remove favourite                   |
| GET    | /api/notifications           | Get user notifications             |
| POST   | /api/alert-rules             | Create alert rule                  |

Requirements:

- Return JSON responses.
- Use appropriate HTTP status codes.
- Validate request parameters.
- Use consistent response structures.
- Protect user-specific endpoints with authentication.
- Implement authorization checks.

---

# 9. Application Architecture

Use modular PHP architecture.

Proposed directory structure:

hazewatch/
|
|-- app/
| |-- Controllers/
| |-- Models/
| |-- Services/
| |-- Repositories/
| |-- Middleware/
| |-- Validators/
|
|-- config/
| |-- app.php
| |-- database.php
|
|-- database/
| |-- migrations/
| |-- seeders/
|
|-- public/
| |-- assets/
| |-- index.php
| |-- .htaccess
|
|-- resources/
| |-- views/
| |-- components/
|
|-- routes/
| |-- web.php
| |-- api.php
|
|-- storage/
| |-- logs/
| |-- cache/
|
|-- cron/
| |-- fetch-air-quality.php
| |-- process-alerts.php
|
|-- tests/
|
|-- .env.example
|-- .gitignore
|-- README.md

## 9.1 Architectural Responsibilities

Controllers:

- Handle HTTP requests.
- Validate request flow.
- Return responses.

Models:

- Represent database entities.

Services:

- Handle business logic.
- Process external API data.
- Calculate analytics.
- Process alerts.

Repositories:

- Handle database queries.

Middleware:

- Handle authentication.
- Handle authorization.

Cron:

- Run scheduled data collection.
- Process alert conditions.

---

# 10. Security Requirements

Implement:

1. Password hashing using password_hash().
2. Password verification using password_verify().
3. PDO prepared statements.
4. CSRF protection.
5. XSS prevention through output escaping.
6. Server-side input validation.
7. Secure PHP session configuration.
8. Session regeneration after login.
9. Login rate limiting.
10. Role-based authorization.
11. Secure environment variable handling.
12. Restricted access to administrative functions.
13. Proper error handling without exposing sensitive information.
14. Secure API request handling.

Never commit .env files or API credentials to GitHub.

---

# 11. Non-Functional Requirements

| Category          | Requirement                              |
| ----------------- | ---------------------------------------- |
| Performance       | Avoid unnecessary external API requests. |
| Reliability       | Handle API failures gracefully.          |
| Security          | Follow PHP security best practices.      |
| Database          | Maintain referential integrity.          |
| Responsive Design | Support desktop, tablet and mobile.      |
| Timezone          | Asia/Kuala_Lumpur for display.           |
| Maintainability   | Modular and reusable code.               |
| Scalability       | Allow additional API providers.          |
| Logging           | Record application and API errors.       |
| Data Quality      | Detect stale and invalid readings.       |

---

# 12. Testing Requirements

Test the following:

## Authentication

- User registration.
- Duplicate email registration.
- Login with valid credentials.
- Login with invalid credentials.
- Logout.
- Unauthorized access.

## API Integration

- Successful API response.
- Invalid API response.
- API timeout.
- API unavailable.
- Missing measurements.
- Unexpected units.
- Duplicate measurements.

## Database

- Foreign key constraints.
- Duplicate prevention.
- Transaction rollback.
- Historical reading retrieval.

## Air Quality Monitoring

- Location search.
- Current measurement display.
- Historical chart data.
- Missing measurement handling.
- Stale data identification.

## Alert System

- Threshold detection.
- Notification creation.
- Duplicate notification prevention.
- Notification read status.

## Security

- SQL injection prevention.
- XSS prevention.
- CSRF protection.
- Session security.
- Authorization checks.

---

# 13. Development Roadmap

Recommended development milestones:

| Milestone | Scope                                  |
| --------- | -------------------------------------- |
| M1        | Project setup and architecture         |
| M2        | Database implementation                |
| M3        | External API integration               |
| M4        | Public dashboard                       |
| M5        | Location details and historical charts |
| M6        | Authentication and favourites          |
| M7        | Analytics and alert system             |
| M8        | Cron jobs and automation               |
| M9        | Admin dashboard                        |
| M10       | Testing and deployment                 |

Develop one milestone at a time.

Each milestone must have clear acceptance criteria before proceeding.

---

# 14. MVP Acceptance Criteria

The MVP is complete when:

- [ ] Public dashboard is accessible.
- [ ] Users can register and log in.
- [ ] Users can log out securely.
- [ ] External API integration works.
- [ ] API measurements are stored in MySQL.
- [ ] Duplicate measurements are prevented.
- [ ] Users can search monitored locations.
- [ ] Users can view location details.
- [ ] Historical charts display stored measurements.
- [ ] Users can save favourite locations.
- [ ] Users can configure alert thresholds.
- [ ] In-app notifications work.
- [ ] Cron jobs automatically collect measurements.
- [ ] API errors are logged.
- [ ] Admin dashboard displays synchronization status.
- [ ] Application is responsive.
- [ ] Application follows security requirements.
- [ ] README contains setup and deployment instructions.

---

# 15. Out of Scope

The following features are excluded from the initial MVP:

- Native Android application.
- Native iOS application.
- Machine learning prediction.
- AI pollution forecasting.
- IoT sensor hardware integration.
- Paid subscriptions.
- Social media integration.
- Complex weather forecasting.

These may be considered in future versions.

---

# 16. Codex Development Instructions

You are working as a senior PHP developer assisting me in building HazeWatch.

I am learning PHP and MySQL while developing this project using AI assistance.

Follow these instructions throughout development:

1. Use Core PHP 8.2+.
2. Use MySQL 8.
3. Use PDO for database access.
4. Follow the architecture described in this document.
5. Do not use Laravel.
6. Do not use React or Node.js.
7. Write readable and maintainable code.
8. Explain important implementation decisions.
9. Do not invent external API response fields.
10. Inspect API documentation before integration.
11. Implement security best practices.
12. Write tests for important business logic.
13. Do not skip database constraints.
14. Do not hardcode credentials.
15. Avoid unnecessary dependencies.
16. Update README when necessary.
17. Keep the implementation aligned with this PRD.

## Development Workflow

For every milestone:

1. Review the requirements.
2. Identify the files that need to be created or modified.
3. Explain the implementation plan.
4. Implement the required functionality.
5. Run relevant tests.
6. Fix any failures.
7. Summarize the changes.
8. Explain how I can manually verify the functionality.
9. Update the project documentation.
10. Wait for my approval before moving to the next milestone.

Do not implement multiple milestones without approval.

Testing should be meaningful and proportionate to the changes. Do not repeat the entire test suite unnecessarily when only an unrelated small change is made.

If requirements are unclear, ask before making assumptions.

---

# 17. Project Success Definition

HazeWatch should become a functional, reliable and portfolio-quality PHP + MySQL application.

The project should demonstrate my ability to:

- Develop a modular PHP application.
- Design relational databases.
- Build REST API endpoints.
- Integrate external APIs.
- Process and normalize data.
- Implement business logic.
- Build data analytics and visualizations.
- Automate background tasks using cron.
- Implement authentication and security.
- Troubleshoot and test backend applications.

The final result should be suitable for demonstration in a junior PHP developer or junior full-stack developer portfolio.

**End of PRD**
