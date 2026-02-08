import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import invoicesApi from '../../api/invoices';
import plansApi from '../../api/plans';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Badge from '../../components/common/Badge';
import Alert from '../../components/common/Alert';
import DeleteConfirmModal from '../../components/common/DeleteConfirmModal';
import { formatCurrency, formatDate } from '../../utils/formatCurrency';
import { INVOICE_STATUSES } from '../../utils/constants';

export default function InvoiceList() {
    const [invoices, setInvoices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [deleteModal, setDeleteModal] = useState({ open: false, invoice: null });
    const [deleting, setDeleting] = useState(false);
    const [exporting, setExporting] = useState(null);
    const [canExport, setCanExport] = useState(false);
    const [message, setMessage] = useState(null);
    const navigate = useNavigate();

    useEffect(() => {
        loadInvoices();
    }, [search, status]);

    useEffect(() => {
        plansApi.getUserLimits().then(res => {
            setCanExport(res.data.data.features?.export_csv || false);
        }).catch(() => {});
    }, []);

    const loadInvoices = async () => {
        try {
            const response = await invoicesApi.getAll({ search, status: status || undefined });
            setInvoices(response.data.data);
        } catch (err) {
            console.error('Failed to load invoices:', err);
            setMessage({ type: 'error', text: 'Failed to load invoices. Please try again.' });
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
        } catch (err) {
            console.error('Failed to delete invoice:', err);
            setMessage({ type: 'error', text: 'Failed to delete invoice. Please try again.' });
        } finally {
            setDeleting(false);
        }
    };

    const handleExport = async (format) => {
        setExporting(format);
        try {
            const params = { search: search || undefined, status: status || undefined };
            const response = format === 'csv'
                ? await invoicesApi.exportCsv(params)
                : await invoicesApi.exportExcel(params);

            const ext = format === 'csv' ? 'csv' : 'xlsx';
            const blob = new Blob([response.data]);
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `invoices-${new Date().toISOString().slice(0, 10)}.${ext}`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            const msg = error.response?.status === 403
                ? 'Export is available on Pro and Business plans. Please upgrade.'
                : 'Failed to export invoices.';
            setMessage({ type: 'error', text: msg });
        } finally {
            setExporting(null);
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
                <div className="flex gap-2">
                    {canExport && (
                        <>
                            <Button
                                variant="secondary"
                                onClick={() => handleExport('csv')}
                                loading={exporting === 'csv'}
                                disabled={!!exporting}
                            >
                                Export CSV
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={() => handleExport('excel')}
                                loading={exporting === 'excel'}
                                disabled={!!exporting}
                            >
                                Export Excel
                            </Button>
                        </>
                    )}
                    <Button onClick={() => navigate('/invoices/create')}>
                        Create Invoice
                    </Button>
                </div>
            </div>

            {message && (
                <Alert variant={message.type} onClose={() => setMessage(null)}>
                    {message.text}
                </Alert>
            )}

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

            <DeleteConfirmModal
                isOpen={deleteModal.open}
                onClose={() => setDeleteModal({ open: false, invoice: null })}
                onConfirm={handleDelete}
                loading={deleting}
                title="Delete Invoice"
            >
                <p>Are you sure you want to delete invoice <strong>{deleteModal.invoice?.invoice_number}</strong>? This action cannot be undone.</p>
            </DeleteConfirmModal>
        </div>
    );
}
