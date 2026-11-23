# Gig Matching System

Web-Based Gig Matching System with Faceted Search and Job Tracking. Final Year Project, UiTM Jasin.

Gig Workers search and apply for short-term jobs of up to 30 days. Contract Entities post jobs, accept one worker, review the submitted work and confirm payment, which happens outside the system. Built with Laravel 13, Blade, custom CSS and JavaScript, and MySQL.

## Quick start

```powershell
php artisan migrate:fresh --seed   # wipes all data, then seeds locations and demo data
php artisan serve                  # http://localhost:8000
php artisan schedule:work          # in a second terminal: automatic approval and payment reminders
```

### Demo accounts

All demo accounts use the password `Password123`.

| Email | Role | What is seeded |
|---|---|---|
| employer@example.com | Contract Entity, business | Open jobs, two applicants on "Event crew", a job in progress with submissions |
| employer2@example.com | Contract Entity, individual | Open jobs, a paid job ready for feedback |
| worker@example.com | Gig Worker | One application waiting, one job in progress, one paid job ready for feedback |
| worker2@example.com | Gig Worker | One application waiting, one cancelled |

## Setup this machine still needs

1. **Upload limits.** Submissions allow 5 files of 10 MB each. In `C:\Program Files\php-8.5.8\php.ini`, set the lines below, then restart `php artisan serve`.

   ```ini
   upload_max_filesize = 10M
   post_max_size = 55M
   ```

2. **Mailtrap.** Emails go to `storage/logs/laravel.log` until you add your Mailtrap inbox credentials to `.env`. The `.env.example` file shows the four lines to change.
3. **Scheduler.** Automatic approval and payment reminders only run while `php artisan schedule:work` is running, or when Windows Task Scheduler runs `php artisan schedule:run` every minute.

## Rules that can be changed without code

These live in `config/gigs.php` and can be overridden in `.env`.

| Setting | Default | Meaning |
|---|---|---|
| GIG_AUTO_APPROVE_DAYS | 7 | A pending submission is approved automatically after this many days |
| GIG_REPORT_THRESHOLD | 3 | A listing is hidden when this many distinct users report it |
| GIG_PAYMENT_REMINDER_DAYS | 3 | One reminder email this many days after the worker finishes |
| max_job_days | 30 | Longest job, counting both the start and end date |

## Database

Eleven tables: users, gig_workers, contract_entities, locations, jobs, applications, submissions, submission_files, comments, reviews, reports. Category is an ENUM on jobs, so there is no category table. Locations are seeded with Malaysian states and districts.

Where Laravel conventions are kept, the ERD names map like this:

| ERD | Code |
|---|---|
| USER.user_id, JOB.job_id and every other `<table>_id` primary key | `id` on each table. Foreign keys keep the ERD names, such as `job_id` and `application_id` |
| USER.full_name | users.name |
| USER.password_hash | users.password, nullable so it can be cleared on account deletion |
| USER.created_at | users.created_at and users.updated_at |
| not in ERD | users.remember_token, for "remember me" |
| Singular table names | Plural table names, such as `jobs` and `submission_files` |

Laravel's database queue also wants a table called `jobs`, so the queue stays on `sync`. Emails are sent immediately.

## Use cases

| ID | Use case | Where |
|---|---|---|
| UC01 | Search Job | JobController@index |
| UC02 | View Job Details | JobController@show |
| UC03 | Apply Job | ApplicationController@store |
| UC04 | Withdraw Application | ApplicationController@withdraw |
| UC05 | View Application | ApplicationController@index and @show |
| UC06 | Submit Work | WorkController@store |
| UC07 | Finish Job | ApplicationController@finish |
| UC08 | Report Listing | ReportController@store |
| UC09 | Post Job | JobController@create and @store |
| UC10 | View Posted Job | JobController@postings and @manage |
| UC11 | Edit Posted Job | JobController@edit and @update |
| UC12 | Close Job | JobController@close |
| UC13 | Verify Applicants | ApplicationController@accept and @reject |
| UC14 | Approve Submission | WorkController@approve and @requestChanges |
| UC15 | Confirm Payment | ApplicationController@confirmPayment |
| UC16 | Manage Profile | ProfileController@edit and @update |
| UC17 | View History | ApplicationController@history and JobController@history |
| UC18 | Track Job Progress | ApplicationController@show |
| UC19 | Post Comment | CommentController@store |
| UC20 | Give Feedback | FeedbackController@store |
| UC21 | Delete Account | ProfileController@destroy |

The job tracker rules from spec section 4 are in `app/Policies/ApplicationPolicy.php`. Scheduled tasks are `gigs:auto-approve` and `gigs:payment-reminders`.

## Security

- **Role-based access.** `role:gig_worker` and `role:contract_entity` middleware return 403 across roles. Policies check ownership and the tracker stage on every action.
- **Verify-later email.** Accounts work straight away. Posting, applying and reporting need a verified email.
- **Private files.** Uploads are stored in `storage/app/private` and only the two parties of a job can download them.
- **Validation.** Every form goes through a Form Request. Eloquent bindings prevent SQL injection and Blade escaping prevents stored XSS.
- **Headers and sessions.** A strict Content-Security-Policy with no inline scripts or styles, CSRF tokens on every form, bcrypt password hashing and rate limits on login, registration, posting and applying.
- **Personal data.** Phone numbers are shown only after acceptance. Deleting an account anonymises the name, email, phone and profile but keeps job records for the other party.

## Interface

- **Logo.** A job ticket with an amber spark, matching the ticket-style job cards. Drawn inline by `<x-logo>`. Files for the report and slides are in `public/images` (`logo.svg`, `logo-on-teal.svg` for teal or dark backgrounds, `logo-512.png`). Tab and home-screen icons are `public/favicon.svg`, `public/favicon.ico` and `public/apple-touch-icon.png`.
- **Look.** Deep teal for actions, amber for pay and ratings, a warm cream background and rounded cards. Colours, corners and spacing are variables at the top of `public/css/app.css`.
- **Font.** Plus Jakarta Sans, stored in `public/fonts` so it works offline and with the Content-Security-Policy. SIL Open Font Licence, see `public/fonts/OFL-plus-jakarta-sans.txt`.
- **Icons.** Line icons from [Lucide](https://lucide.dev) (ISC licence), drawn inline through `<x-icon name="...">`. The paths are in `app/Support/Icons.php`. Each job category has its own icon and tint (`JobCategory::icon()` and `tone()`).
- **Find Jobs.** On wide screens, clicking a job card slides its details in from the right while the list squashes into one column. Category, search and page changes update the results in place. Without JavaScript every link and form still works as a normal page load.
- **Pay filter.** Under More filters, pay starts with Any, Per hour, Per day or Fixed, because those amounts can't be compared. A two-handle slider then sets the range, with a dot for every matching open gig. The slider's ends come from the open jobs of that type (`JobController::payRange`), and the `pay_type` filter is part of the search.
- **Comment threads.** Each comment has a Reply button. A reply is stored with `parent_id` and shown under the comment it answers, indented. After three levels replies line up and say who they answer. New comments still go at the bottom, and the old rules stay: no editing or deleting, and the thread is read-only once payment is confirmed.
- **In-person jobs.** Jobs with nothing to upload (every category except Design & Digital and Data Entry & Admin) use different words for the same work step: the worker clicks "Mark as done" (a note and optional photos), and the employer clicks "Confirm" or "Send back". Statuses read "Waiting for confirmation", "Confirmed done" and "Sent back", and submissions are called reports. The steps, rules and data are unchanged. All the wording lives in `app/Support/WorkWording.php`.
- **Loading and confirmations.** Skeleton screens show while pages load. Forms with `data-confirm` open a styled dialog, and short messages appear as a toast.

## Tests

```powershell
php artisan test
```

The tests cover registration, roles, posting and editing rules, search facets, every tracker transition, file validation and privacy, automatic approval, reports, feedback, payment reminders and account deletion. `PageRenderTest` loads the demo data and opens every page for both roles.
