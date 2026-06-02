import express from 'express';
import { existsSync } from 'node:fs';
import { unlink } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import puppeteer from 'puppeteer';
import qrcode from 'qrcode-terminal';
import pkg from 'whatsapp-web.js';

const { Client, LocalAuth, MessageMedia } = pkg;

const app = express();
const port = Number(process.env.WHATSAPP_PORT || 3000);
const host = process.env.WHATSAPP_HOST || '127.0.0.1';
const authToken = process.env.WHATSAPP_TOKEN || '';
const browserPath = process.env.CHROME_PATH || [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    `${process.env.LOCALAPPDATA}\\Google\\Chrome\\Application\\chrome.exe`,
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
].find((path) => path && existsSync(path));

app.use(express.json());

let isReady = false;

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: 'servicos-provedor',
        dataPath: '.whatsapp-session',
    }),
    puppeteer: {
        headless: true,
        executablePath: browserPath,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
});

client.on('qr', (qr) => {
    isReady = false;
    console.log('Escaneie o QR Code abaixo com o WhatsApp do provedor:');
    qrcode.generate(qr, { small: true });
});

client.on('loading_screen', (percent, message) => {
    console.log(`Carregando WhatsApp Web: ${percent}% - ${message}`);
});

client.on('authenticated', () => {
    console.log('WhatsApp autenticado. Aguardando carregamento das conversas...');
});

client.on('ready', () => {
    isReady = true;
    console.log('WhatsApp conectado e pronto para enviar mensagens.');
});

client.on('auth_failure', (message) => {
    isReady = false;
    console.error('Falha na autenticacao do WhatsApp:', message);
});

client.on('disconnected', (reason) => {
    isReady = false;
    console.warn('WhatsApp desconectado:', reason);
});

client.on('change_state', (state) => {
    console.log('Estado do WhatsApp:', state);
});

function authorize(req, res, next) {
    if (!authToken) {
        next();
        return;
    }

    const header = req.get('authorization') || '';
    if (header !== `Bearer ${authToken}`) {
        res.status(401).json({ error: 'Token invalido.' });
        return;
    }

    next();
}

function normalizeBrazilianPhone(phone) {
    const digits = String(phone || '').replace(/\D/g, '');

    if (digits.length < 10) {
        return null;
    }

    return digits.startsWith('55') ? digits : `55${digits}`;
}

function normalizeGroupId(groupId) {
    const value = String(groupId || '').trim();

    return value.endsWith('@g.us') ? value : null;
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

async function gerarImagemEnderecoSgp({ baseUrl, clienteUrl, username, password, browserExecutablePath }) {
    const timeout = 90000;
    const normalizedBaseUrl = normalizeBaseUrl(baseUrl);
    const normalizedClienteUrl = resolveUrl(normalizedBaseUrl, clienteUrl);
    const outputPath = join(tmpdir(), `sgp-endereco-${Date.now()}-${randomUUID()}.png`);
    let browser;

    if (!normalizedBaseUrl) {
        throw new Error('Informe um base_url valido para o SGP.');
    }

    if (!normalizedClienteUrl) {
        throw new Error('Informe um cliente_url valido para o SGP.');
    }

    if (!username || !password) {
        throw new Error('Informe username e password do SGP.');
    }

    try {
        browser = await puppeteer.launch({
            headless: 'new',
            executablePath: browserExecutablePath || undefined,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
        });

        const page = await browser.newPage();
        page.setDefaultTimeout(timeout);
        await page.setViewport({
            width: 1440,
            height: 2000,
            deviceScaleFactor: 1,
        });

        await autenticarNoSgp(page, normalizedBaseUrl, normalizedClienteUrl, username, password, timeout);
        await page.goto(normalizedClienteUrl, { waitUntil: 'networkidle2', timeout });
        await new Promise((resolve) => setTimeout(resolve, 700));

        const clip = await localizarEndereco(page);

        if (!clip) {
            throw new Error('Nao foi possivel localizar a area do endereco no SGP.');
        }

        await page.screenshot({
            path: outputPath,
            clip,
            type: 'png',
        });

        return outputPath;
    } catch (error) {
        try {
            await unlink(outputPath);
        } catch (cleanupError) {
            if (cleanupError?.code !== 'ENOENT') {
                console.warn('Nao foi possivel remover o arquivo temporario da captura do SGP:', outputPath, cleanupError);
            }
        }

        throw error;
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

app.get('/status', (req, res) => {
    res.json({ ready: isReady });
});

app.post('/send-message', authorize, async (req, res) => {
    if (!isReady) {
        res.status(503).json({ error: 'WhatsApp ainda nao esta conectado.' });
        return;
    }

    const groupId = normalizeGroupId(req.body.group_id);
    const phone = normalizeBrazilianPhone(req.body.phone);
    const message = String(req.body.message || '').trim();

    if ((!groupId && !phone) || !message) {
        res.status(422).json({ error: 'Informe group_id ou phone, e message validos.' });
        return;
    }

    try {
        if (groupId) {
            await client.sendMessage(groupId, message);
            res.json({ sent: true, target: 'group' });
            return;
        }

        const numberId = await client.getNumberId(phone);

        if (!numberId) {
            res.status(422).json({
                error: 'Numero nao encontrado no WhatsApp.',
                phone,
            });
            return;
        }

        await client.sendMessage(numberId._serialized, message);
        res.json({ sent: true, target: 'phone' });
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);
        res.status(500).json({ error: 'Nao foi possivel enviar a mensagem.' });
    }
});

app.post('/send-media', authorize, async (req, res) => {
    if (!isReady) {
        res.status(503).json({ error: 'WhatsApp ainda nao esta conectado.' });
        return;
    }

    const groupId = normalizeGroupId(req.body.group_id);
    const phone = normalizeBrazilianPhone(req.body.phone);
    const filePath = String(req.body.file_path || '').trim();
    const caption = String(req.body.caption || '').trim();
    const deleteAfterSend = req.body.delete_after_send !== false && req.body.delete_after_send !== '0';

    if ((!groupId && !phone) || !filePath) {
        res.status(422).json({ error: 'Informe group_id ou phone, e file_path validos.' });
        return;
    }

    try {
        const media = MessageMedia.fromFilePath(filePath);
        const captionContent = caption || ' ';

        if (groupId) {
            await client.sendMessage(groupId, captionContent, { media });
            res.json({ sent: true, target: 'group' });
            return;
        }

        const numberId = await client.getNumberId(phone);

        if (!numberId) {
            res.status(422).json({
                error: 'Numero nao encontrado no WhatsApp.',
                phone,
            });
            return;
        }

        await client.sendMessage(numberId._serialized, captionContent, { media });
        res.json({ sent: true, target: 'phone' });
    } catch (error) {
        console.error('Erro ao enviar midia:', error);
        res.status(500).json({ error: 'Nao foi possivel enviar a midia.' });
    } finally {
        if (deleteAfterSend) {
            try {
                await unlink(filePath);
            } catch (error) {
                if (error?.code !== 'ENOENT') {
                    console.warn('Nao foi possivel remover o arquivo temporario:', filePath, error);
                }
            }
        }
    }
});

app.post('/send-sgp-address', authorize, async (req, res) => {
    if (!isReady) {
        res.status(503).json({ error: 'WhatsApp ainda nao esta conectado.' });
        return;
    }

    const groupId = normalizeGroupId(req.body.group_id);
    const phone = normalizeBrazilianPhone(req.body.phone);
    const baseUrl = String(req.body.base_url || '').trim();
    const clienteUrl = String(req.body.cliente_url || '').trim();
    const username = String(req.body.username || '').trim();
    const password = String(req.body.password || '').trim();
    const caption = String(req.body.caption || '').trim();

    if ((!groupId && !phone) || !baseUrl || !clienteUrl || !username || !password) {
        res.status(422).json({
            error: 'Informe group_id ou phone, base_url, cliente_url, username e password validos.',
        });
        return;
    }

    let filePath;

    try {
        filePath = await gerarImagemEnderecoSgp({
            baseUrl,
            clienteUrl,
            username,
            password,
            browserExecutablePath: browserPath,
        });

        const media = MessageMedia.fromFilePath(filePath);

        if (groupId) {
            await client.sendMessage(groupId, media, caption ? { caption } : undefined);
            res.json({ sent: true, target: 'group' });
            return;
        }

        const numberId = await client.getNumberId(phone);

        if (!numberId) {
            res.status(422).json({
                error: 'Numero nao encontrado no WhatsApp.',
                phone,
            });
            return;
        }

        await client.sendMessage(numberId._serialized, media, caption ? { caption } : undefined);
        res.json({ sent: true, target: 'phone' });
    } catch (error) {
        console.error('Erro ao capturar ou enviar a imagem do endereco do SGP:', error);
        res.status(500).json({ error: 'Nao foi possivel capturar ou enviar a imagem.' });
    } finally {
        if (filePath) {
            try {
                await unlink(filePath);
            } catch (error) {
                if (error?.code !== 'ENOENT') {
                    console.warn('Nao foi possivel remover o arquivo temporario:', filePath, error);
                }
            }
        }
    }
});

app.get('/groups', authorize, async (req, res) => {
    if (!isReady) {
        res.status(503).json({ error: 'WhatsApp ainda nao esta conectado.' });
        return;
    }

    const chats = await client.getChats();

    const groups = chats
        .filter((chat) => chat.isGroup)
        .map((group) => ({
            name: group.name,
            id: group.id._serialized,
        }));

    res.json(groups);
});

console.log(`Navegador usado pelo WhatsApp: ${browserPath || 'cache do Puppeteer'}`);
console.log('Inicializando WhatsApp Web...');

client.initialize().catch((error) => {
    console.error('Erro ao inicializar o WhatsApp Web:', error);
});

app.listen(port, host, () => {
    console.log(`Servico WhatsApp rodando em http://${host}:${port}`);
});
