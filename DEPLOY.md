# Putting Servease online as a public demo

This guide puts Servease on the internet as a **public portfolio demo**: anyone can open it, tap a demo account on the sign-in page and try everything. Demo mode keeps it usable for the next visitor:

- the demo accounts (every `@servease.test` email) can't have their details, password or photo changed, can't be suspended, and get no password-reset links
- uploads are turned off, so strangers can't publish images or documents on your site
- the first visit of each day puts the demo data back, so whatever visitors changed is gone

Any host with **PHP 8.1+ and MySQL or MariaDB** works. These steps use [InfinityFree](https://www.infinityfree.com): it's free, needs no card, and gives you PHP 8.3, MySQL and a free subdomain. It has no SSH, so everything is done in its control panel and File Manager.

## 1. Create the site and the database

1. Sign in to InfinityFree and click **Create Account** to add a hosting account for Servease. Pick a free subdomain, for example `servease-ansao.infinityfreeapp.com`.
2. Open the account's **Control Panel** → **MySQL Databases**. Create a database named `servease`.
3. Write down the four database details shown there:
   - **MySQL Host Name**, like `sql123.infinityfree.com`
   - **MySQL DB Name**, like `if0_12345678_servease`
   - **MySQL User Name**, like `if0_12345678`
   - **MySQL Password**: your hosting account password (shown under **Account Details**)

## 2. Upload the files

1. In the account, open **File Manager** and go into the `htdocs` folder. Delete the default `index2.html` if it's there.
2. Upload **`deploy/servease-upload.zip`** into `htdocs`, then right-click it and choose **Extract**. Delete the zip afterwards.
   (No zip? Upload every file and folder of the project except `tests/`, `docs/`, `_old/`, `deploy/` and `config/config.php`.)
3. In `htdocs/config/`, create a file named **`config.php`** with the content of [`deploy/config.example.php`](#settings-file) below, and fill in the four database details from step 1.

## 3. Install the demo data

1. Open `https://YOUR-SUBDOMAIN/install` (for example `https://servease-ansao.infinityfreeapp.com/install`).
2. Keep **Load demo data** ticked and click **Install Servease**. It takes a few seconds.
3. The installer locks itself once the database is set up, so nobody else can run it.

## 4. Turn on HTTPS

In the control panel, open **Free SSL Certificates**, request a certificate for your subdomain and install it. It can take a little while to be issued. After that, always share the `https://` link.

## 5. Check it

- Open the site: the intro screens show, then the sign-in page with three demo accounts and the line "This is a public demo that resets every day."
- Sign in as each demo account (Customer, Pro, Admin) and click around.
- As the customer, try to change the password on **Settings**: it should say it's a shared demo account.

## Settings file

`config/config.php` on the server (never commit it: it holds your database password):

```php
<?php
return [
    'app' => [
        'env'         => 'production',
        'debug'       => false,
        'demo'        => true,    // one-click demo accounts on the sign-in page
        'public_demo' => true,    // protect demo accounts, uploads off, daily reset
    ],
    'db' => [
        'host' => 'sql123.infinityfree.com',   // MySQL Host Name
        'port' => 3306,
        'name' => 'if0_12345678_servease',     // MySQL DB Name
        'user' => 'if0_12345678',              // MySQL User Name
        'pass' => 'your-hosting-password',     // MySQL Password
    ],
    'mail' => [
        'driver'     => 'log',                 // free hosting can't send email; messages are logged instead
        'from_email' => 'no-reply@servease.test',
    ],
];
```

## Good to know

- **Email:** free InfinityFree accounts can't send email, so sign-up confirmation and reset emails are only logged. Visitors can still create their own accounts and use everything; the email just shows as "not verified".
- **Daily reset:** InfinityFree has no scheduled jobs, so the reset runs on the first visit of each day instead. That one visit takes a few seconds longer.
- **Reminders** (`database/reminders.php`) need a scheduled job, so they don't run on free hosting.
- **Updating the site later:** upload the changed files over the old ones in File Manager. Your `config/config.php` and the database stay as they are.
- **Running it for real instead of as a demo:** set `public_demo` to `false` and `demo` to `false`, and change or delete the demo accounts.
