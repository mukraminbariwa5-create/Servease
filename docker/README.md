# Servease public demo on Render (free)

This runs Servease as a **public portfolio demo** in one container: PHP 8.3 and Apache with a small MariaDB inside it. It's an alternative to [DEPLOY.md](../DEPLOY.md) (shared PHP hosting).

- **Fresh data on every start.** The database is created and filled with the demo customers, pros, bookings and reviews each time the container starts, and again on the first visit of each day.
- **Public demo mode is on** ([`docker/config.php`](config.php)): one-click demo accounts on the sign-in page, demo accounts protected, uploads off.
- **Free plan sleep.** Render's free plan sleeps after 15 minutes without visitors and takes about a minute to wake on the next visit.

## Put it online

1. Sign up at [render.com](https://render.com) with your GitHub account.
2. **New** → **Blueprint**, pick the **Servease** repository, and confirm. Render reads [`render.yaml`](../render.yaml) and builds the [`Dockerfile`](../Dockerfile).
3. When it says **Live**, open the address Render gives you (like `https://servease-demo.onrender.com`).

Every push to `main` deploys again automatically. The **Demo container** workflow builds and checks this image on every push.
