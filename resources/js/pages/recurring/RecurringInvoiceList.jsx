import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import recurringInvoicesApi from '../../api/recurringInvoices';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Alert from '../../components/common/Alert';
import DeleteConfirmModal from '../../components/common/DeleteConfirmModal';
import { formatDate } from '../../utils/formatCurrency';
import { RECURRING_FREQUENCIES } from '../../utils/constants';

export default function RecurringInvoiceList() {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [deleteModal, setDeleteModal] = useState({ open: false, item: null });
    const [deleting, setDeleting] = useState(false);
    const [message, setMessage] = useState(null);
    const navigate = useNavigate();

    useEffect(() => {
        loadItems();
    }, []);

    const loadItems = async () => {
        try {
            const response = await recurringInvoicesApi.getAll();
            setItems(response.data.data);
        } catch (err) {
            console.error('Failed to load recurring invoices:', err);
            setMessage({ type: 'error', text: 'Failed to load recurring invoices. Please try again.' });
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async () => {
        if (!deleteModal.item) return;
        setDeleting(true);
        try {
            await recurringInvoicesApi.delete(deleteModal.item.id);
            setItems(items.filter(i => i.id !== deleteModal.item.id));
            setDeleteModal({ open: false, item: null });
        } catch (err) {
            console.error('Failed to delete:', err);
            setMessage({ type: 'error', text: 'Failed to delete recurring invoice. Please try again.' });
        } finally {
            setDeleting(false);
        }
    };

    const handleToggle = async (item) => {
        try {
            const response = await recurringInvoicesApi.toggleActive(item.id);
            setItems(items.map(i => i.id === item.id ? { ...i, is_active: response.data.is_active } : i));
            setMessage({
                type: 'success',
                text: response.data.is_active ? 'Recurring invoice activated.' : 'Recurring invoice paused.',
            });
        } catch (error) {
            setMessage({ type: 'error', text: 'Failed to update status.' });
        }
    };

    const getFrequencyLabel = (freq) => {
        return RECURRING_FREQUENCIES.find(f => f.value === freq)?.label || freq;
    };

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Recurring Invoices</h1>
                    <p className="text-gray-600">Manage your automated invoice schedules</p>
                </div>
                <Button onClick={() => navigate('/recurring/create')}>
                    Create Recurring Invoice
                </Button>
            </div>

            {message && (
                <Alert variant={message.type} onClose={() => setMessage(null)}>
                    {message.text}
                </Alert>
            )}

            <Card>
                {loading ? (
                    <div className="flex justify-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                ) : items.length === 0 ? (
                    <div className="text-center py-8 text-gray-500">
                        No recurring invoices set up yet
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Frequency</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Generated</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {items.map((item) => (
                                    <tr key={item.id}>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">
                                            {item.client?.name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-600">
                                            {getFrequencyLabel(item.frequency)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-600">
                                            {formatDate(item.next_generate_date)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-600">
                                            {item.total_generated}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Badge color={item.is_active ? 'green' : 'gray'}>
                                                {item.is_active ? 'Active' : 'Paused'}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <button
                                                onClick={() => handleToggle(item)}
                                                className="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                {item.is_active ? 'Pause' : 'Resume'}
                                            </button>
                                            <Link
                                                to={`/recurring/${item.id}/edit`}
                                                className="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                onClick={() => setDeleteModal({ open: true, item })}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            <DeleteConfirmModal
                isOpen={deleteModal.open}
                onClose={() => setDeleteModal({ open: false, item: null })}
                onConfirm={handleDelete}
                loading={deleting}
                title="Delete Recurring Invoice"
            >
                <p>Are you sure you want to delete this recurring invoice for <strong>{deleteModal.item?.client?.name}</strong>? This will not delete any invoices already generated.</p>
            </DeleteConfirmModal>
        </div>
    );
}
