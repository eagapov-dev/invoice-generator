import React from 'react';
import invoicesApi from '../../api/invoices';
import InvoiceForm from './InvoiceForm';

export default function InvoiceCreate() {
    return (
        <InvoiceForm
            mode="create"
            title="Create Invoice"
            subtitle="Create a new invoice"
            submitLabel="Create Invoice"
            loadSettingsCurrency
            onSubmit={(data) => invoicesApi.create(data)}
        />
    );
}
