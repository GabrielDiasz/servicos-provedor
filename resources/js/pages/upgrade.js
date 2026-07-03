const STATUS_STYLES = {
    aguardando: {
        label: 'Aguardando',
        classes: 'bg-slate-500/15 text-slate-300 border-slate-500/30',
    },
    na_fila: {
        label: 'Na fila',
        classes: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
    },
    enviando: {
        label: 'Enviando',
        classes: 'bg-blue-500/15 text-blue-300 border-blue-500/30',
    },
    enviado: {
        label: 'Enviado',
        classes: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
    },
    erro: {
        label: 'Erro',
        classes: 'bg-rose-500/15 text-rose-300 border-rose-500/30',
    },
    ignorado: {
        label: 'Ignorado',
        classes: 'bg-zinc-500/15 text-zinc-300 border-zinc-500/30',
    },
};

function updateStatusBadge(badge, status) {
    if (!badge) return;

    const normalized = STATUS_STYLES[status] || STATUS_STYLES.aguardando;
    badge.textContent = normalized.label;
    badge.className = `inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ${normalized.classes}`;
}

function formatDateTime(value) {
    if (!value) return '-';

    const date = new Date(value.replace(' ', 'T'));

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(date);
}

function initUpgradePage() {
    const page = document.querySelector('[data-upgrade-page]');

    if (!page) {
        return;
    }

    const searchInput = page.querySelector('[data-upgrade-search]');
    const selectAllInputs = Array.from(page.querySelectorAll('[data-upgrade-select-all]'));
    const sendForm = page.querySelector('[data-upgrade-send-form]');
    const sendSelectedButton = page.querySelector('[data-upgrade-scope="selected"]');
    const sendAllButton = page.querySelector('[data-upgrade-scope="all"]');
    const refreshButton = page.querySelector('[data-upgrade-refresh]');
    const liveMessage = page.querySelector('[data-upgrade-live-message]');
    const selectedCount = Array.from(page.querySelectorAll('[data-upgrade-selected-count]'));
    const totalCount = Array.from(page.querySelectorAll('[data-upgrade-total-count]'));
    const sentCount = Array.from(page.querySelectorAll('[data-upgrade-sent-count]'));
    const failedCount = Array.from(page.querySelectorAll('[data-upgrade-failed-count]'));
    const progressBar = page.querySelector('[data-upgrade-progress-bar]');
    const progressLabel = page.querySelector('[data-upgrade-progress-label]');
    const statusLabel = page.querySelector('[data-upgrade-status-label]');
    const statusBadge = page.querySelector('[data-upgrade-status-badge]');
    const erroUltimo = page.querySelector('[data-upgrade-error-last]');
    const pollingUrl = page.dataset.statusUrl || '';
    let pollTimer = null;
    let fastPollTimer = null;
    const lockedStatuses = new Set(['enviado', 'na_fila', 'enviando', 'ignorado']);
    const pendingRows = new Set();

    const rows = () => Array.from(page.querySelectorAll('[data-upgrade-row]'));
    const checkboxes = () => Array.from(page.querySelectorAll('[data-upgrade-checkbox]'));

    const setTextOnAll = (elements, value) => {
        elements.forEach((element) => {
            element.textContent = String(value);
        });
    };

    const setLiveMessage = (message, type = 'info') => {
        if (!liveMessage) return;

        liveMessage.textContent = message || '';
        liveMessage.classList.remove('hidden', 'border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-200', 'border-rose-500/30', 'bg-rose-500/10', 'text-rose-200');

        if (!message) {
            liveMessage.classList.add('hidden');
            return;
        }

        if (type === 'success') {
            liveMessage.classList.add('border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-200');
            return;
        }

        if (type === 'error') {
            liveMessage.classList.add('border-rose-500/30', 'bg-rose-500/10', 'text-rose-200');
            return;
        }

        liveMessage.classList.add('border-[#3a3a40]', 'bg-[#2a2a2e]', 'text-slate-200');
    };

    const setButtonBusy = (button, isBusy) => {
        if (!button) return;

        button.disabled = isBusy;

        if (isBusy) {
            button.dataset.upgradeTempDisabled = '1';
            return;
        }

        if (button.dataset.upgradeTempDisabled === '1') {
            button.disabled = false;
            delete button.dataset.upgradeTempDisabled;
        }
    };

    const syncRowControls = (row, status) => {
        if (!row) return;

        const rowId = row.dataset.upgradeRowId;
        const checkbox = row.querySelector('[data-upgrade-checkbox]');
        const selectTrigger = row.querySelector('[data-upgrade-select]');
        const sendButton = row.querySelector('[data-upgrade-send-single]');
        const normalizedStatus = (status || 'aguardando').toLowerCase();
        const isLocked = lockedStatuses.has(normalizedStatus) || pendingRows.has(rowId);

        if (checkbox) {
            checkbox.disabled = isLocked;
            if (isLocked) {
                checkbox.checked = false;
            }
        }

        if (selectTrigger) {
            selectTrigger.disabled = isLocked;
        }

        setButtonBusy(sendButton, isLocked);
    };

    const setRowBusy = (rowId, isBusy) => {
        const row = page.querySelector(`[data-upgrade-row-id="${rowId}"]`);

        if (!row) return;

        if (isBusy) {
            pendingRows.add(rowId);
        }

        if (!isBusy) {
            pendingRows.delete(rowId);
        }

        setButtonBusy(row.querySelector('[data-upgrade-send-single]'), isBusy);
    };

    const submitUpgradeSend = async ({ scope, submitter, rowId = null } = {}) => {
        if (!sendForm || !submitter) {
            return;
        }

        const formData = new FormData(sendForm);
        formData.set('scope', scope);
        let requestSucceeded = false;

        if (scope === 'selected') {
            const checkedIds = checkboxes()
                .filter((checkbox) => checkbox.checked && !checkbox.disabled)
                .map((checkbox) => checkbox.value);

            formData.delete('selected_ids[]');
            checkedIds.forEach((id) => formData.append('selected_ids[]', id));
        }

        try {
            setButtonBusy(submitter, true);
            if (rowId) {
                setRowBusy(rowId, true);
            }

            setLiveMessage('Iniciando envio do Upgrade...', 'info');

            const response = await fetch(sendForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(payload?.message || 'Nao foi possivel iniciar o envio.');
            }

            setLiveMessage(payload?.message || 'Envio iniciado com sucesso.', 'success');
            startFastPolling();
            await pollStatus();
            requestSucceeded = true;
        } catch (error) {
            setLiveMessage(error?.message || 'Falha ao iniciar o envio.', 'error');
        } finally {
            setButtonBusy(submitter, false);
            if (rowId) {
                const row = page.querySelector(`[data-upgrade-row-id="${rowId}"]`);
                if (!requestSucceeded) {
                    pendingRows.delete(rowId);
                } else if (row?.dataset?.upgradeStatus && row.dataset.upgradeStatus !== 'aguardando') {
                    pendingRows.delete(rowId);
                }
                syncRowControls(row, row?.dataset?.upgradeStatus || 'aguardando');
            }
        }
    };

    const updateSelected = () => {
        const checked = checkboxes().filter((checkbox) => checkbox.checked && !checkbox.disabled);
        setTextOnAll(selectedCount, checked.length);

        if (selectAllInputs.length > 0) {
            const visibleRows = rows().filter((row) => !row.classList.contains('hidden'));
            const visibleCheckboxes = visibleRows.map((row) => row.querySelector('[data-upgrade-checkbox]')).filter(Boolean);
            const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every((checkbox) => checkbox.checked);

            selectAllInputs.forEach((input) => {
                input.checked = allChecked;
            });
        }
    };

    const applySearch = () => {
        const query = (searchInput?.value || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

        rows().forEach((row) => {
            const name = (row.dataset.upgradeName || '').toLowerCase();
            const show = !query || name.includes(query);

            row.classList.toggle('hidden', !show);
        });

        updateSelected();
    };

    const setSummary = (campaign) => {
        if (!campaign) return;

        setTextOnAll(totalCount, campaign.total_clientes ?? '0');
        setTextOnAll(sentCount, campaign.enviados ?? '0');
        setTextOnAll(failedCount, campaign.falhas ?? '0');
        if (statusLabel) statusLabel.textContent = campaign.status_label || '-';
        if (statusBadge) {
            statusBadge.textContent = campaign.status_label || '-';
            statusBadge.className = `inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${campaign.status_badge_class || ''}`;
        }
        if (erroUltimo) erroUltimo.textContent = campaign.erro_ultimo || 'Nenhum erro registrado.';

        const selected = Number(campaign.selecionados || 0);
        const processed = Number(campaign.enviados || 0) + Number(campaign.falhas || 0);
        const percent = selected > 0 ? Math.min(100, Math.round((processed / selected) * 100)) : 0;

        if (progressBar) {
            progressBar.style.width = `${percent}%`;
        }

        if (progressLabel) {
            progressLabel.textContent = `${percent}%`;
        }
    };

    const updateRow = (contact) => {
        const row = page.querySelector(`[data-upgrade-row-id="${contact.id}"]`);

        if (!row) return;

        const status = (contact.status_envio || 'aguardando').toLowerCase();
        row.dataset.upgradeStatus = status;

        if (status !== 'aguardando') {
            pendingRows.delete(String(contact.id));
        }

        const badge = row.querySelector('[data-upgrade-status-badge]');
        updateStatusBadge(badge, status);

        const errorCell = row.querySelector('[data-upgrade-error]');
        if (errorCell) {
            errorCell.textContent = contact.erro_envio || '—';
            errorCell.classList.toggle('text-rose-400', Boolean(contact.erro_envio));
            errorCell.classList.toggle('text-slate-400', !contact.erro_envio);
        }

        const sentCell = row.querySelector('[data-upgrade-sent-at]');
        if (sentCell) {
            sentCell.textContent = formatDateTime(contact.enviado_em);
        }

        syncRowControls(row, status);
    };

    const syncFromPayload = (payload) => {
        if (!payload) return;

        setSummary(payload.campaign);
        (payload.contatos || []).forEach(updateRow);
        updateSelected();
    };

    const pollStatus = async () => {
        if (!pollingUrl) return;

        try {
            const response = await fetch(pollingUrl, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            syncFromPayload(payload);

            const campaignStatus = payload?.campaign?.status_envio;
            if (campaignStatus && ['concluido', 'concluido_com_erro', 'erro', 'importado'].includes(campaignStatus)) {
                clearInterval(pollTimer);
                pollTimer = null;
                clearInterval(fastPollTimer);
                fastPollTimer = null;
            }

            return payload;
        } catch (error) {
            console.error('Falha ao atualizar status do Upgrade.', error);
            return null;
        }
    };

    const startFastPolling = () => {
        clearInterval(fastPollTimer);
        fastPollTimer = setInterval(async () => {
            const payload = await pollStatus();
            const campaignStatus = payload?.campaign?.status_envio;

            if (campaignStatus && ['concluido', 'concluido_com_erro', 'erro', 'importado'].includes(campaignStatus)) {
                clearInterval(fastPollTimer);
                fastPollTimer = null;
            }
        }, 1200);
    };

    if (searchInput) {
        searchInput.addEventListener('input', applySearch);
    }

    selectAllInputs.forEach((selectAll) => selectAll.addEventListener('change', () => {
        checkboxes().forEach((checkbox) => {
            if (checkbox.disabled) {
                return;
            }

            if (checkbox.closest('[data-upgrade-row]')?.classList.contains('hidden')) {
                return;
            }

            checkbox.checked = selectAll.checked;
        });

        updateSelected();
    }));

    page.addEventListener('change', (event) => {
        if (event.target?.matches?.('[data-upgrade-checkbox]')) {
            updateSelected();
        }
    });

    page.addEventListener('click', (event) => {
        const target = event.target.closest?.('[data-upgrade-send-single]');

        if (!target) {
            return;
        }

        const rowId = target.dataset.upgradeSendSingle;
        const row = page.querySelector(`[data-upgrade-row-id="${rowId}"]`);
        const checkbox = row?.querySelector('[data-upgrade-checkbox]');

        if (!checkbox || !sendForm) {
            return;
        }

        checkboxes().forEach((item) => {
            item.checked = item === checkbox;
        });

        if (target.disabled) {
            return;
        }

        updateSelected();
        void submitUpgradeSend({
            scope: 'selected',
            submitter: target,
            rowId,
        });
    });

    sendForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitter = event.submitter;
        const scope = submitter?.dataset?.upgradeScope === 'all' ? 'all' : 'selected';
        void submitUpgradeSend({ scope, submitter });
    });

    if (refreshButton) {
        refreshButton.addEventListener('click', () => {
            pollStatus();
        });
    }

    updateSelected();
    applySearch();
    rows().forEach((row) => {
        syncRowControls(row, row.dataset.upgradeStatus || 'aguardando');
    });

    if (pollingUrl) {
        pollTimer = setInterval(pollStatus, 2500);
        pollStatus();
    }

}

document.addEventListener('DOMContentLoaded', initUpgradePage);
