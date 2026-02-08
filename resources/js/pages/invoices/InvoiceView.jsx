import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import invoicesApi from '../../api/invoices';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Alert from '../../components/common/Alert';
import { formatCurrency, formatDate } from '../../utils/formatCurrency';
import { INVOICE_STATUSES, PDF_TEMPLATES } from '../../utils/constants';

export default function InvoiceView() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [invoice, setInvoice] = useState(null);
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const [message, setMessage] = useState(null);
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        loadInvoice();
    }, [id]);

    const loadInvoice = async () => {
        try {
            const response = await invoicesApi.get(id);
            setInvoice(response.data.data);
        } catch (err) {
            console.error('Failed to load invoice:', err);
            if (err.response?.status === 404) {
                navigate('/invoices');
            } else {
                setMessage({ type: 'error', text: 'Failed to load invoice. Please try again.' });
            }
        } finally {
            setLoading(false);
        }
    };

    const handleStatusChange = async (status) => {
        try {
            await invoicesApi.updateStatus(id, status);
            setInvoice(prev => ({ ...prev, status }));
            setMessage({ type: 'success', text: 'Status updated successfully.' });
        } catch (error) {
            setMessage({ type: 'error', text: 'Failed to update status.' });
        }
    };

    const handleSend = async () => {
        setSending(true);
        try {
            await invoicesApi.send(id);
            setInvoice(prev => ({ ...prev, status: prev.status === 'draft' ? 'sent' : prev.status }));
            setMessage({ type: 'success', text: 'Invoice sent successfully!' });
        } catch (error) {
            setMessage({ type: 'error', text: error.response?.data?.message || 'Failed to send invoice.' });
        } finally {
            setSending(false);
        }
    };

    const handleToggleShare = async () => {
        try {
            const response = await invoicesApi.toggleShare(id);
            setInvoice(prev => ({
                ...prev,
                public_token: response.data.shared ? response.data.public_url.split('/p/')[1] : null,
                public_url: response.data.public_url,
            }));
            setMessage({
                type: 'success',
                text: response.data.shared ? 'Public link enabled.' : 'Public link disabled.',
            });
        } catch (error) {
            setMessage({ type: 'error', text: 'Failed to update sharing settings.' });
        }
    };

    const handleCopyLink = () => {
        if (invoice.public_url) {
            navigator.clipboard.writeText(invoice.public_url);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const handleDownloadPdf = async () => {
        try {
            const response = await invoicesApi.getPdfUrl(id);
            window.open(response.data.url, '_blank');
        } catch (error) {
            setMessage({ type: 'error', text: 'Failed to download PDF.' });
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    if (!invoice) return null;

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-start">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Invoice {invoice.invoice_number}</h1>
                    <p className="text-gray-600">
                        Created on {formatDate(invoice.created_at)}
                    </p>
                </div>
                <div className="flex gap-3">
                    <Button variant="secondary" onClick={handleDownloadPdf}>
                        Download PDF
                    </Button>
                    <Button variant="secondary" onClick={handleSend} loading={sending}>
                        Send to Client
                    </Button>
                    <Link to={`/invoices/${id}/edit`}>
                        <Button>Edit</Button>
                    </Link>
                </div>
            </div>

            {message && (
                <Alert variant={message.type} onClose={() => setMessage(null)}>
                    {message.text}
                </Alert>
            )}

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-6">
                    <Card title="Client Information">
                        <div className="space-y-2">
                            <p className="font-medium text-gray-900">{invoice.client?.name}</p>
                            {invoice.client?.company && (
                                <p className="text-gray-600">{invoice.client.company}</p>
                            )}
                            {invoice.client?.email && (
                                <p className="text-gray-600">{invoice.client.email}</p>
                            )}
                            {invoice.client?.phone && (
                                <p className="text-gray-600">{invoice.client.phone}</p>
                            )}
                            {invoice.client?.address && (
                                <p className="text-gray-600 whitespace-pre-line">{invoice.client.address}</p>
                            )}
                        </div>
                    </Card>

                    <Card title="Items">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {invoice.items?.map((item, index) => (
                                        <tr key={index}>
                                            <td className="px-4 py-3 text-gray-900">{item.description}</td>
                                            <td className="px-4 py-3 text-right text-gray-600">{item.quantity}</td>
                                            <td className="px-4 py-3 text-right text-gray-600">{formatCurrency(item.price, invoice.currency)}</td>
                                            <td className="px-4 py-3 text-right text-gray-900 font-medium">{formatCurrency(item.total, invoice.currency)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4 border-t pt-4">
                            <div className="flex justify-end">
                                <div className="w-64 space-y-2">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Subtotal:</span>
                                        <span className="font-medium">{formatCurrency(invoice.subtotal, invoice.currency)}</span>
                                    </div>
                                    {invoice.tax_percent > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Tax ({invoice.tax_percent}%):</span>
                                            <span className="font-medium">{formatCurrency(invoice.subtotal * invoice.tax_percent / 100, invoice.currency)}</span>
                                        </div>
                                    )}
                                    {invoice.discount > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Discount:</span>
                                            <span className="font-medium text-red-600">-{formatCurrency(invoice.discount, invoice.currency)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between border-t pt-2 text-lg font-bold">
                                        <span>Total:</span>
                                        <span>{formatCurrency(invoice.total, invoice.currency)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {invoice.notes && (
                        <Card title="Notes">
                            <p className="text-gray-600 whitespace-pre-line">{invoice.notes}</p>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card title="Status">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-gray-600">Current Status:</span>
                                <Badge color={INVOICE_STATUSES[invoice.status]?.color}>
                                    {INVOICE_STATUSES[invoice.status]?.label}
                                </Badge>
                            </div>
                            <div className="border-t pt-4 space-y-2">
                                <p className="text-sm font-medium text-gray-700 mb-2">Change Status:</p>
                                <div className="flex flex-wrap gap-2">
                                    {Object.entries(INVOICE_STATUSES).map(([value, { label, color }]) => (
                                        <button
                                            key={value}
                                            onClick={() => handleStatusChange(value)}
                                            className={`px-3 py-1 text-sm rounded-full border transition-colors ${
                                                invoice.status === value
                                                    ? 'bg-gray-200 border-gray-400'
                                                    : 'hover:bg-gray-100 border-gray-300'
                                            }`}
                                        >
                                            {label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card title="Invoice Details">
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Invoice Number:</span>
                                <span className="font-medium">{invoice.invoice_number}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Issue Date:</span>
                                <span className="font-medium">{formatDate(invoice.created_at)}</span>
                            </div>
                            {invoice.due_date && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Due Date:</span>
                                    <span className="font-medium">{formatDate(invoice.due_date)}</span>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <span className="text-gray-600">Template:</span>
                                <span className="font-medium">
                                    {PDF_TEMPLATES.find(t => t.value === invoice.pdf_template)?.label || 'Classic'}
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card title="Share">
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-gray-600">Public Link</span>
                                <button
                                    onClick={handleToggleShare}
                                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                        invoice.public_token ? 'bg-blue-600' : 'bg-gray-200'
                                    }`}
                                >
                                    <span
                                        className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                            invoice.public_token ? 'translate-x-6' : 'translate-x-1'
                                        }`}
                                    />
                                </button>
                            </div>
                            {invoice.public_token && invoice.public_url && (
                                <div>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="text"
                                            readOnly
                                            value={invoice.public_url}
                                            className="flex-1 text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1.5 text-gray-600"
                                        />
                                        <button
                                            onClick={handleCopyLink}
                                            className="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors whitespace-nowrap"
                                        >
                                            {copied ? 'Copied!' : 'Copy'}
                                        </button>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1.5">
                                        Anyone with this link can view and download the invoice.
                                    </p>
                                </div>
                            )}
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    );
}
