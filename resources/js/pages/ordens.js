window.ordensPage = () => ({
    deleteModalOpen: false,
    deleteAction: '',
    deleteLabel: '',
    whatsappModalOpen: false,
    whatsappAction: '',
    whatsappLabel: '',
    whatsappAbrirSgp: false,
    whatsappReady: false,
    whatsappCountdown: 5,
    whatsappTimer: null,
    whatsappReadyTimer: null,
    filtersOpen: false,

    clearWhatsappTimers() {
        clearInterval(this.whatsappTimer);
        clearTimeout(this.whatsappReadyTimer);
        this.whatsappTimer = null;
        this.whatsappReadyTimer = null;
    },

    openWhatsappModal(action, label) {
        this.whatsappAction = action;
        this.whatsappLabel = label;
        this.whatsappAbrirSgp = false;
        this.whatsappReady = false;
        this.whatsappCountdown = 5;
        this.whatsappModalOpen = true;
        this.clearWhatsappTimers();

        this.whatsappTimer = setInterval(() => {
            if (this.whatsappCountdown > 1) {
                this.whatsappCountdown -= 1;
                return;
            }

            this.whatsappCountdown = 0;
            clearInterval(this.whatsappTimer);
            this.whatsappTimer = null;
        }, 1000);

        this.whatsappReadyTimer = setTimeout(() => {
            if (!this.whatsappModalOpen) {
                return;
            }

            this.whatsappReady = true;
            this.whatsappCountdown = 0;
            clearInterval(this.whatsappTimer);
            this.whatsappTimer = null;
        }, 3000);
    },

    closeWhatsappModal() {
        this.whatsappModalOpen = false;
        this.whatsappReady = false;
        this.whatsappCountdown = 5;
        this.whatsappAbrirSgp = false;
        this.whatsappAction = '';
        this.whatsappLabel = '';
        this.clearWhatsappTimers();
    },

    openDeleteModal(action, label) {
        this.deleteAction = action;
        this.deleteLabel = label;
        this.deleteModalOpen = true;
    },

    closeDeleteModal() {
        this.deleteModalOpen = false;
        this.deleteAction = '';
        this.deleteLabel = '';
    },
});
