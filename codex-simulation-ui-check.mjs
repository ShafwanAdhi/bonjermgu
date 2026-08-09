import { chromium } from "playwright";

const baseUrl = process.env.PLAYWRIGHT_BASE_URL ?? "http://127.0.0.1:8077";

async function login(page) {
    await page.goto(`${baseUrl}/login`, { waitUntil: "domcontentloaded", timeout: 10000 });
    await page.locator('input[wire\\:model="username"]').fill("budisantoso");
    await page.locator('input[wire\\:model="password"]').fill("ref-budisantoso");
    await page.getByRole("button", { name: /^Masuk$/ }).click();
    await page.waitForURL(/\/dashboard$/, { timeout: 10000 });
}

async function checkViewport(browser, viewport, label) {
    const context = await browser.newContext({ viewport, ...(viewport.width < 600 ? { isMobile: true, hasTouch: true } : {}) });
    const page = await context.newPage();
    const failures = [];

    await login(page);
    await page.goto(`${baseUrl}/simulation`, { waitUntil: "domcontentloaded", timeout: 10000 });

    const resetButton = page.getByRole("button", { name: "Hapus Data" });
    await resetButton.waitFor({ state: "visible", timeout: 10000 });

    const resetBox = await resetButton.boundingBox();
    const calculateBox = await page.getByRole("button", { name: "Hitung Simulasi" }).boundingBox();

    if (!resetBox || resetBox.width < 44 || resetBox.height < 44) {
        failures.push(`${label}: Hapus Data touch target is too small`);
    }

    if (viewport.width >= 600 && calculateBox && resetBox && Math.abs(calculateBox.y - resetBox.y) > 4) {
        failures.push(`${label}: Hapus Data is not aligned with Hitung Simulasi`);
    }

    await page.getByLabel("Type Debitur").selectOption("legal_entity");
    await page.goto(`${baseUrl}/dashboard`, { waitUntil: "domcontentloaded", timeout: 10000 });
    await page.goto(`${baseUrl}/simulation`, { waitUntil: "domcontentloaded", timeout: 10000 });

    const restoredType = await page.getByLabel("Type Debitur").inputValue();
    if (restoredType !== "legal_entity") {
        failures.push(`${label}: temporary form state was not restored`);
    }

    page.once("dialog", (dialog) => dialog.accept());
    await resetButton.click();
    await page.waitForTimeout(500);

    const clearedType = await page.getByLabel("Type Debitur").inputValue();
    if (clearedType !== "non_entrepreneur") {
        failures.push(`${label}: Hapus Data did not reset debtor type`);
    }

    const metrics = await page.evaluate(() => ({
        width: document.documentElement.clientWidth,
        scrollWidth: document.documentElement.scrollWidth,
    }));

    if (metrics.scrollWidth > metrics.width) {
        failures.push(`${label}: horizontal overflow detected`);
    }

    await context.close();
    return { label, failures };
}

const browser = await chromium.launch({ headless: true });
const results = [];

try {
    results.push(await checkViewport(browser, { width: 1440, height: 900 }, "desktop"));
    results.push(await checkViewport(browser, { width: 390, height: 844 }, "mobile"));
} finally {
    await browser.close();
}

console.log(JSON.stringify(results, null, 2));

const failures = results.flatMap((result) => result.failures);
if (failures.length) {
    console.error(failures.join("\n"));
    process.exit(1);
}
