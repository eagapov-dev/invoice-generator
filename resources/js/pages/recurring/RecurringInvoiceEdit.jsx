import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import recurringInvoicesApi from '../../api/recurringInvoices';
import RecurringInvoiceForm from './RecurringInvoiceForm';

export default function RecurringInvoiceEdit() {
    const navigate = useNavigate();
    const { id } = useParams();
    const [fetching, setFetching] = useState(true);
    const [initialForm, setInitialForm] = useState(null);
    const [initialItems, setInitialItems] = useState(null);

    useEffect(() => {
        loadData();
    }, [id]);

    const loadData = async () => {
        try {
            const response = await recurringInvoicesApi.get(id);
            const recurring = response.data.data;
            setInitialForm({
                client_id: recurring.client_id?.toString() || '',
                frequency: recurring.frequency || 'monthly',
                end_date: recurring.end_date || '',
                currency: recurring.currency || 'USD',
                tax_percent: recurring.tax_percent?.toString() || '0',
                discount: recurring.discount?.toString() || '0',
                notes: recurring.notes || '',
            });
            setInitialItems(recurring.items?.map(item => ({
                id: item.id,
                product_id: item.product_id?.toString() || '',
                description: item.description || '',
                quantity: item.quantity?.toString() || '1',
                price: item.price?.toString() || '0',
            })) || []);
        } catch (err) {
            console.error('Failed to load data:', err);
            if (err.response?.status === 404) {
                navigate('/recurring');
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
        <RecurringInvoiceForm
            mode="edit"
            initialForm={initialForm}
            initialItems={initialItems}
            title="Edit Recurring Invoice"
            subtitle="Update recurring invoice schedule"
            submitLabel="Save Changes"
            onSubmit={(data) => recurringInvoicesApi.update(id, data)}
        />
    );
}
