import React from 'react';
import recurringInvoicesApi from '../../api/recurringInvoices';
import RecurringInvoiceForm from './RecurringInvoiceForm';

export default function RecurringInvoiceCreate() {
    return (
        <RecurringInvoiceForm
            mode="create"
            title="Create Recurring Invoice"
            subtitle="Set up an automated invoice schedule"
            submitLabel="Create Recurring Invoice"
            loadSettingsCurrency
            onSubmit={(data) => recurringInvoicesApi.create(data)}
        />
    );
}
