import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import invoicesApi from '../../api/invoices';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Badge from '../../components/common/Badge';
import Modal from '../../components/common/Modal';
import { formatCurrency, formatDate } from '../../utils/formatCurrency';
import { INVOICE_STATUSES } from '../../utils/constants';

export default function InvoiceList() {
    const [invoices, setInvoices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [deleteModal, setDeleteModal] = useState({ open: false, invoice: null });
    const [deleting, setDeleting] = useState(false);
    const navigate = useNavigate();

    useEffect(() => {
        loadInvoices();
    }, [search, status]);

    const loadInvoices = async () => {
        try {
            const response = await invoicesApi.getAll({ search, status: status || undefined });
            setInvoices(response.data.data);
        } catch (error) {
            console.error('Failed to load invoices:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async () => {
        if (!deleteModal.invoice) return;
        setDeleting(true);
        try {
            await invoicesApi.delete(deleteModal.invoice.id);
            setInvoices(invoices.filter(i => i.id !== deleteModal.invoice.id));
            setDeleteModal({ open: false, invoice: null });
        } catch (error) {
            console.error('Failed to delete invoice:', error);
        } finally {
            setDeleting(false);
        }
    };

    const statusOptions = [
        { value: '', label: 'All Statuses' },
        ...Object.entries(INVOICE_STATUSES).map(([value, { label }]) => ({ value, label })),
    ];

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Invoices</h1>
                    <p className="text-gray-600">Manage your invoices</p>
                </div>
                <Button onClick={() => navigate('/invoices/create')}>
                    Create Invoice
                </Button>
            </div>

            <Card>
                <div className="flex flex-wrap gap-4 mb-4">
                    <Input
                        type="search"
                        placeholder="Search invoices..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-64"
                    />
                    <Select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        options={statusOptions}
                        placeholder="All Statuses"
                        className="w-48"
                    />
                </div>

                {loading ? (
                    <div className="flex justify-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                ) : invoices.length === 0 ? (
                    <div className="text-center py-8 text-gray-500">
                        No invoices found
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {invoices.map((invoice) => (
                                    <tr key={invoice.id}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Link to={`/invoices/${invoice.id}`} className="text-blue-600 hover:text-blue-900 font-medium">
                                                {invoice.invoice_number}
                                            </Link>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-900">
                                            {invoice.client?.name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">
                                            {formatCurrency(invoice.total)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Badge color={INVOICE_STATUSES[invoice.status]?.color}>
                                                {INVOICE_STATUSES[invoice.status]?.label}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-500">
                                            {formatDate(invoice.due_date) || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <Link
                                                to={`/invoices/${invoice.id}`}
                                                className="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                to={`/invoices/${invoice.id}/edit`}
                                                className="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                onClick={() => setDeleteModal({ open: true, invoice })}
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

            <Modal
                isOpen={deleteModal.open}
                onClose={() => setDeleteModal({ open: false, invoice: null })}
                title="Delete Invoice"
            >
                <p className="text-gray-600 mb-4">
                    Are you sure you want to delete invoice <strong>{deleteModal.invoice?.invoice_number}</strong>? This action cannot be undone.
                </p>
                <div className="flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => setDeleteModal({ open: false, invoice: null })}>
                        Cancel
                    </Button>
                    <Button variant="danger" onClick={handleDelete} loading={deleting}>
                        Delete
                    </Button>
                </div>
            </Modal>
        </div>
    );
}
