import express from 'express';
import { existsSync } from 'node:fs';
import qrcode from 'qrcode-terminal';
import pkg from 'whatsapp-web.js';

const { Client, LocalAuth } = pkg;

const app = express();
const port = Number(process.env.WHATSAPP_PORT || 3000);
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

app.get('/groups', async (req, res) => {
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

app.listen(port, () => {
    console.log(`Servico WhatsApp rodando em http://localhost:${port}`);
});
