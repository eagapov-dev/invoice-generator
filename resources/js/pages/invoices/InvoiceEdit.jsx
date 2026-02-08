import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import invoicesApi from '../../api/invoices';
import InvoiceForm from './InvoiceForm';

export default function InvoiceEdit() {
    const navigate = useNavigate();
    const { id } = useParams();
    const [fetching, setFetching] = useState(true);
    const [initialForm, setInitialForm] = useState(null);
    const [initialItems, setInitialItems] = useState(null);

    useEffect(() => {
        loadInvoice();
    }, [id]);

    const loadInvoice = async () => {
        try {
            const response = await invoicesApi.get(id);
            const invoice = response.data.data;
            setInitialForm({
                client_id: invoice.client_id?.toString() || '',
                currency: invoice.currency || 'USD',
                pdf_template: invoice.pdf_template || 'classic',
                tax_percent: invoice.tax_percent?.toString() || '0',
                discount: invoice.discount?.toString() || '0',
                status: invoice.status || 'draft',
                due_date: invoice.due_date || '',
                notes: invoice.notes || '',
            });
            setInitialItems(invoice.items?.map(item => ({
                id: item.id,
                product_id: item.product_id?.toString() || '',
                description: item.description || '',
                quantity: item.quantity?.toString() || '1',
                price: item.price?.toString() || '0',
            })) || []);
        } catch (err) {
            console.error('Failed to load invoice:', err);
            if (err.response?.status === 404) {
                navigate('/invoices');
            }
        } finally {
            setFetching(false);
        }
    };

    if (fetching) {
        return (
            <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    return (
        <InvoiceForm
            mode="edit"
            initialForm={initialForm}
            initialItems={initialItems}
            title="Edit Invoice"
            subtitle="Update invoice details"
            submitLabel="Save Changes"
            onSubmit={(data) => invoicesApi.update(id, data)}
        />
    );
}
