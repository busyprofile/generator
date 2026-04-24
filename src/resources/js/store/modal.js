import {defineStore} from 'pinia'

export const useModalStore = defineStore('modal', {
    state: () => {
        return {
            modals: {
                confirm: {
                    showed: false,
                    params: {},
                },
                dispute: {
                    showed: false,
                    params: {},
                },
                disputeCancel: {
                    showed: false,
                    params: {},
                },
                deposit: {
                    showed: false,
                    params: {},
                },
                withdrawal: {
                    showed: false,
                    params: {},
                },
                order: {
                    showed: false,
                    params: {},
                },
                notification: {
                    showed: false,
                    params: {},
                },
                payout: {
                    showed: false,
                    params: {},
                },
                editOrderAmount: {
                    showed: false,
                    params: {},
                },
                userNotes: {
                    showed: false,
                    params: {},
                },
            },
        }
    },
    getters: {
        confirmModal: (state) => state.modals.confirm,
        disputeModal: (state) => state.modals.dispute,
        disputeCancelModal: (state) => state.modals.disputeCancel,
        depositModal: (state) => state.modals.deposit,
        withdrawalModal: (state) => state.modals.withdrawal,
        orderModal: (state) => state.modals.order,
        notificationModal: (state) => state.modals.notification,
        payoutModal: (state) => state.modals.payout,
        editOrderAmountModal: (state) => state.modals.editOrderAmount,
        userNotesModal: (state) => state.modals.userNotes,
    },
    actions: {
        openModal(name, params = {}) {
            this.modals[name].showed = true;
            this.modals[name].params = params;
        },
        closeModal(name) {
            this.modals[name].showed = false;
            this.modals[name].params = {};
        },
        openConfirmModal({
             title,
             body = 'Действие невозможно отменить.',
             confirm_button_name = 'Подтвердить',
             cancel_button_name = 'Отмена',
             confirm = null,
             close = null
        } = {}) {
            this.openModal('confirm', {
                title,
                body,
                confirm_button_name,
                cancel_button_name,
                confirm,
                close
            });
        },
        openDisputeModal(props) {
            this.openModal('dispute', props);
        },
        openDisputeCancelModal(props) {
            this.openModal('disputeCancel', props);
        },
        openDepositModal(props) {
            this.openModal('deposit', props);
        },
        openWithdrawalModal(props) {
            this.openModal('withdrawal', props);
        },
        openOrderModal(props) {
            this.openModal('order', props);
        },
        openNotificationModal(props) {
            this.openModal('notification', props);
        },
        openPayoutModal(props) {
            this.openModal('payout', props);
        },
        openEditOrderAmountModal(props) {
            this.openModal('editOrderAmount', props);
        },
        openUserNotesModal(props) {
            this.openModal('userNotes', props);
        },
        closeAll() {
            for (const modal_name in this.modals) {
                this.closeModal(modal_name)
            }
        }
    },
})
