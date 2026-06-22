/**
 * Captures real screenshots of UniGPT's core pages for the presentation demo
 * carousel. Drives the installed Google Chrome headlessly via puppeteer-core,
 * logging in through the /demo-login buttons (creds: demo123) for each role.
 *
 * Output: public/demo/*.png  (served at /demo/*.png)
 * Run:    node scripts/capture-demo.mjs
 */
import puppeteer from 'puppeteer-core';
import { mkdirSync } from 'node:fs';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8001';
const OUT = 'public/demo';
const CHROME = '/usr/bin/google-chrome';
const VW = 1600, VH = 900;

const PLAN = {
    student: [
        { slug: 'student-chat', path: '/chat', wait: 1800 },
        { slug: 'student-dashboard', path: '/dashboard' },
        { slug: 'student-class-tests', path: '/class-tests' },
        { slug: 'student-assignments', path: '/assignments' },
        { slug: 'student-materials', path: '/materials' },
        { slug: 'student-transcript', path: '/transcript' },
        { slug: 'student-attendance', path: '/attendance' },
        { slug: 'student-roadmap', path: '/roadmap' },
        { slug: 'student-calendar', path: '/calendar' },
        { slug: 'student-exams', path: '/exams' },
    ],
    faculty: [
        { slug: 'faculty-ai', path: '/faculty/ai-assistant', wait: 1800 },
        { slug: 'faculty-dashboard', path: '/faculty/dashboard' },
        { slug: 'faculty-grading', path: '/faculty/grading' },
        { slug: 'faculty-analytics', path: '/faculty/analytics' },
        { slug: 'faculty-class-tests', path: '/faculty/class-tests' },
        { slug: 'faculty-courses', path: '/faculty/courses' },
        { slug: 'faculty-exams', path: '/faculty/exams' },
        { slug: 'faculty-students', path: '/faculty/students' },
    ],
    admin: [
        { slug: 'admin-approvals', path: '/admin/approvals' },
        { slug: 'admin-ai-usage', path: '/admin/ai-usage' },
        { slug: 'admin-users', path: '/admin/users' },
        { slug: 'admin-analytics', path: '/admin/analytics' },
        { slug: 'admin-dashboard', path: '/admin/dashboard' },
        { slug: 'admin-documents', path: '/admin/documents' },
        { slug: 'admin-courses', path: '/admin/courses' },
        { slug: 'admin-departments', path: '/admin/departments' },
        { slug: 'admin-terms', path: '/admin/terms' },
        { slug: 'admin-exams', path: '/admin/exams' },
        { slug: 'admin-roles', path: '/admin/roles' },
        { slug: 'admin-announcements', path: '/admin/announcements' },
    ],
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function loginDemo(page, role) {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0', timeout: 60000 });
    // Click the demo button whose label matches the role.
    const clicked = await page.evaluate((r) => {
        const target = r.charAt(0).toUpperCase() + r.slice(1);
        const btn = [...document.querySelectorAll('button')].find(
            (b) => b.textContent.trim().toLowerCase() === target.toLowerCase()
        );
        if (btn) { btn.click(); return true; }
        return false;
    }, role);
    if (!clicked) throw new Error(`Demo button for ${role} not found`);
    // Wait until we've navigated off /login.
    await page.waitForFunction(() => !location.pathname.startsWith('/login'), { timeout: 30000 });
    await sleep(800);
}

async function main() {
    mkdirSync(OUT, { recursive: true });
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', `--window-size=${VW},${VH}`],
    });

    const results = [];
    for (const [role, pages] of Object.entries(PLAN)) {
        const ctx = await browser.createBrowserContext();
        const page = await ctx.newPage();
        await page.setViewport({ width: VW, height: VH, deviceScaleFactor: 1 });
        // Force the application's LIGHT theme: the boot script in app.blade.php
        // reads localStorage('theme') before paint, and components honour the
        // prefers-color-scheme media feature.
        await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);
        await page.evaluateOnNewDocument(() => {
            try { localStorage.setItem('theme', 'light'); } catch (e) {}
            document.documentElement.classList.remove('dark');
        });
        try {
            await loginDemo(page, role);
            for (const p of pages) {
                try {
                    await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle0', timeout: 45000 });
                    await sleep(p.wait || 900);
                    await page.evaluate(() => window.scrollTo(0, 0));
                    const file = `${OUT}/${p.slug}.png`;
                    await page.screenshot({ path: file });
                    console.log(`  ✓ ${role}: ${p.path} → ${file}`);
                    results.push({ role, ...p, ok: true });
                } catch (e) {
                    console.log(`  ✗ ${role}: ${p.path} — ${e.message}`);
                    results.push({ role, ...p, ok: false, err: e.message });
                }
            }
        } catch (e) {
            console.log(`✗ login ${role}: ${e.message}`);
        }
        await ctx.close();
    }

    await browser.close();
    const ok = results.filter((r) => r.ok).length;
    console.log(`\nDone: ${ok}/${results.length} captured.`);
}

main().catch((e) => { console.error(e); process.exit(1); });
