window.ordensPage = () => ({
    deleteModalOpen: false,
    deleteAction: '',
    deleteLabel: '',
    whatsappModalOpen: false,
    whatsappAction: '',
    whatsappLabel: '',
    whatsappAbrirSgp: false,
    whatsappReady: false,
    whatsappCountdown: 3,
    whatsappTimer: null,
    filtersOpen: false,
    whatsappCountdownDuration: 3,

    clearWhatsappTimers() {
        clearInterval(this.whatsappTimer);
        this.whatsappTimer = null;
    },

    openWhatsappModal(action, label) {
        this.whatsappAction = action;
        this.whatsappLabel = label;
        this.whatsappAbrirSgp = false;
        this.whatsappReady = false;
        this.whatsappCountdown = this.whatsappCountdownDuration;
        this.whatsappModalOpen = true;
        this.clearWhatsappTimers();

        this.whatsappTimer = setInterval(() => {
            if (!this.whatsappModalOpen) {
                this.clearWhatsappTimers();
                return;
            }

            if (this.whatsappCountdown > 1) {
                this.whatsappCountdown -= 1;
                return;
            }

            this.whatsappCountdown = 0;
            this.whatsappReady = true;
            clearInterval(this.whatsappTimer);
            this.whatsappTimer = null;
        }, 1000);
    },

    closeWhatsappModal() {
        this.whatsappModalOpen = false;
        this.whatsappReady = false;
        this.whatsappCountdown = this.whatsappCountdownDuration;
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
