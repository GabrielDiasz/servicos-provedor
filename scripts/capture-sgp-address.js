import puppeteer from 'puppeteer';
import { existsSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';

const args = parseArgs(process.argv.slice(2));
const baseUrl = normalizeBaseUrl(args['base-url'] || process.env.SGP_BASE_URL);
const clienteUrl = resolveUrl(baseUrl, args['cliente-url']);
const outputPath = args.output;
const username = String(args.username || process.env.SGP_WEB_USERNAME || '').trim();
const password = String(args.password || process.env.SGP_WEB_PASSWORD || '').trim();
const browserPath = String(args['browser-path'] || process.env.CHROME_PATH || '').trim() || [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    `${process.env.LOCALAPPDATA}\\Google\\Chrome\\Application\\chrome.exe`,
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
].find((path) => path && existsSync(path));
const timeout = Number.parseInt(args.timeout || '90000', 10);

if (!baseUrl) {
    throw new Error('Informe SGP_BASE_URL ou --base-url.');
}

if (!clienteUrl) {
    throw new Error('Informe --cliente-url com um link valido do cliente no SGP.');
}

if (!outputPath) {
    throw new Error('Informe --output com o caminho do arquivo temporario.');
}

if (!username || !password) {
    throw new Error('Informe --username e --password.');
}

let browser;

try {
    browser = await puppeteer.launch({
        headless: 'new',
        executablePath: browserPath || undefined,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const page = await browser.newPage();
    page.setDefaultTimeout(timeout);
    await page.setViewport({
        width: 1440,
        height: 2000,
        deviceScaleFactor: 1,
    });

    await autenticarNoSgp(page, baseUrl, clienteUrl, username, password, timeout);
    await page.goto(clienteUrl, { waitUntil: 'networkidle2', timeout });
    await new Promise((resolve) => setTimeout(resolve, 700));

    const clip = await localizarEndereco(page);

    if (!clip) {
        throw new Error('Nao foi possivel localizar a area do endereco no SGP.');
    }

    mkdirSync(dirname(outputPath), { recursive: true });
    await page.screenshot({
        path: outputPath,
        clip,
        type: 'png',
    });

    process.stdout.write(JSON.stringify({ output: outputPath }));
} catch (error) {
    console.error(error instanceof Error ? error.message : String(error));
    process.exitCode = 1;
} finally {
    if (browser) {
        await browser.close();
    }
}

function parseArgs(argv) {
    const parsed = {};

    for (let index = 0; index < argv.length; index += 1) {
        const value = argv[index];

        if (!value.startsWith('--')) {
            continue;
        }

        const next = argv[index + 1];
        const [key, inlineValue] = value.slice(2).split('=', 2);

        if (inlineValue !== undefined) {
            parsed[key] = inlineValue;
            continue;
        }

        if (next && !next.startsWith('--')) {
            parsed[key] = next;
            index += 1;
            continue;
        }

        parsed[key] = 'true';
    }

    return parsed;
}

function normalizeBaseUrl(value) {
    const trimmed = String(value || '').trim();

    if (!trimmed) {
        return '';
    }

    return trimmed.endsWith('/') ? trimmed.slice(0, -1) : trimmed;
}

function resolveUrl(baseUrlValue, urlValue) {
    const value = String(urlValue || '').trim();

    if (!value) {
        return '';
    }

    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    return new URL(value, `${baseUrlValue}/`).toString();
}

async function autenticarNoSgp(page, baseUrlValue, clienteUrlValue, user, pass, timeoutValue) {
    const loginUrl = new URL('/accounts/login/', `${baseUrlValue}/`);
    loginUrl.searchParams.set('next', clienteUrlValue);

    await page.goto(loginUrl.toString(), { waitUntil: 'networkidle2', timeout: timeoutValue });

    const userSelector = 'input[name="username"]';
    const passwordSelector = 'input[name="password"]';

    await page.waitForSelector(userSelector, { timeout: timeoutValue });
    await page.waitForSelector(passwordSelector, { timeout: timeoutValue });

    await page.click(userSelector, { clickCount: 3 });
    await page.type(userSelector, user, { delay: 10 });
    await page.click(passwordSelector, { clickCount: 3 });
    await page.type(passwordSelector, pass, { delay: 10 });

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2', timeout: timeoutValue }).catch(() => null),
        page.keyboard.press('Enter'),
    ]);
}

async function localizarEndereco(page) {
    return page.evaluate(async () => {
        const keywords = ['endereco', 'logradouro', 'bairro', 'numero', 'complemento', 'referencia'];
        const normalize = (value = '') => value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

        const hasKeyword = (value = '') => keywords.some((keyword) => normalize(value).includes(keyword));

        const heading = Array.from(document.querySelectorAll('.add_title_sub')).find((element) =>
            normalize(element.textContent || '').includes('endereco do cliente')
        );

        if (heading) {
            const directBlock = heading.nextElementSibling;

            if (directBlock && directBlock.classList.contains('add_content_sub')) {
                const rect = directBlock.getBoundingClientRect();

                if (rect.width > 0 && rect.height > 0) {
                    return {
                        x: Math.max(0, Math.floor(rect.x)),
                        y: Math.max(0, Math.floor(rect.y)),
                        width: Math.ceil(rect.width),
                        height: Math.ceil(rect.height),
                    };
                }
            }

            const ancestorBlock = heading.closest('fieldset, form, section, article, .add_content_sub');

            if (ancestorBlock) {
                const contentBlock = ancestorBlock.querySelector('.add_content_sub');

                if (contentBlock) {
                    const rect = contentBlock.getBoundingClientRect();

                    if (rect.width > 0 && rect.height > 0) {
                        return {
                            x: Math.max(0, Math.floor(rect.x)),
                            y: Math.max(0, Math.floor(rect.y)),
                            width: Math.ceil(rect.width),
                            height: Math.ceil(rect.height),
                        };
                    }
                }
            }
        }

        const matches = Array.from(document.querySelectorAll('body *')).filter((element) => {
            const text = [
                element.textContent || '',
                element.getAttribute('aria-label') || '',
                element.getAttribute('placeholder') || '',
                element.getAttribute('name') || '',
                element.getAttribute('id') || '',
            ].join(' ');

            const rect = element.getBoundingClientRect();

            return rect.width > 0 && rect.height > 0 && hasKeyword(text);
        });

        const regions = [];

        for (const match of matches) {
            let current = match;

            while (current && current !== document.body) {
                const rect = current.getBoundingClientRect();
                const text = normalize(current.innerText || current.textContent || '');
                const fieldCount = current.querySelectorAll('input, textarea, select, label').length;
                const keywordCount = keywords.reduce((count, keyword) => count + (text.includes(keyword) ? 1 : 0), 0);

                const looksLikeRegion = rect.width >= 240
                    && rect.height >= 90
                    && rect.width <= window.innerWidth * 0.98
                    && rect.height <= Math.max(900, window.innerHeight * 0.9)
                    && (fieldCount >= 2 || keywordCount >= 2);

                if (looksLikeRegion) {
                    regions.push({
                        element: current,
                        area: rect.width * rect.height,
                    });
                }

                current = current.parentElement;
            }
        }

        if (regions.length === 0) {
            return null;
        }

        regions.sort((a, b) => a.area - b.area);

        const region = regions[0].element;
        region.scrollIntoView({ block: 'center', inline: 'nearest' });

        await new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(resolve));
        });

        const rect = region.getBoundingClientRect();
        const padding = 20;
        const x = Math.max(0, Math.floor(rect.x - padding));
        const y = Math.max(0, Math.floor(rect.y - padding));
        const width = Math.ceil(rect.width + padding * 2);
        const height = Math.ceil(rect.height + padding * 2);

        return {
            x,
            y,
            width: Math.min(width, Math.max(1, Math.floor(window.innerWidth - x))),
            height: Math.min(height, Math.max(1, Math.floor(window.innerHeight - y))),
        };
    });
}
