import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import invoicesApi from '../../api/invoices';
import { formatCurrency, formatDate } from '../../utils/formatCurrency';

export default function PublicInvoicePage() {
    const { token } = useParams();
    const [invoice, setInvoice] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    useEffect(() => {
        loadInvoice();
    }, [token]);

    const loadInvoice = async () => {
        try {
            const response = await invoicesApi.getPublic(token);
            setInvoice(response.data.data);
        } catch (err) {
            setError(true);
        } finally {
            setLoading(false);
        }
    };

    const handleDownloadPdf = () => {
        window.open(`/api/p/${token}/pdf`, '_blank');
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    if (error || !invoice) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="text-center">
                    <h1 className="text-2xl font-bold text-gray-900 mb-2">Invoice Not Found</h1>
                    <p className="text-gray-600">This invoice link is invalid or has been disabled.</p>
                </div>
            </div>
        );
    }

    const statusColors = {
        draft: 'bg-gray-100 text-gray-700',
        sent: 'bg-blue-100 text-blue-700',
        paid: 'bg-green-100 text-green-700',
        overdue: 'bg-red-100 text-red-700',
    };

    return (
        <div className="min-h-screen bg-gray-50 py-8 px-4">
            <div className="max-w-3xl mx-auto">
                {/* Header */}
                <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
                    <div className="flex justify-between items-start">
                        <div>
                            {invoice.company.logo_url && (
                                <img src={invoice.company.logo_url} alt="Logo" className="h-12 mb-3" />
                            )}
                            <h1 className="text-xl font-bold text-gray-900">
                                {invoice.company.name}
                            </h1>
                            {invoice.company.address && (
                                <p className="text-sm text-gray-600 whitespace-pre-line mt-1">{invoice.company.address}</p>
                            )}
                            {invoice.company.phone && (
                                <p className="text-sm text-gray-600">{invoice.company.phone}</p>
                            )}
                            {invoice.company.email && (
                                <p className="text-sm text-gray-600">{invoice.company.email}</p>
                            )}
                        </div>
                        <div className="text-right">
                            <h2 className="text-2xl font-light text-gray-400 uppercase tracking-wider">Invoice</h2>
                            <p className="text-lg font-semibold text-gray-900 mt-1">{invoice.invoice_number}</p>
                            <span className={`inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold uppercase ${statusColors[invoice.status] || statusColors.draft}`}>
                                {invoice.status_label}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Client + Dates */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div className="bg-white rounded-lg shadow-sm border p-6">
                        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Bill To</h3>
                        <p className="font-semibold text-gray-900">{invoice.client.name}</p>
                        {invoice.client.company && <p className="text-sm text-gray-600">{invoice.client.company}</p>}
                        {invoice.client.address && <p className="text-sm text-gray-600 whitespace-pre-line">{invoice.client.address}</p>}
                        {invoice.client.email && <p className="text-sm text-gray-600">{invoice.client.email}</p>}
                        {invoice.client.phone && <p className="text-sm text-gray-600">{invoice.client.phone}</p>}
                    </div>
                    <div className="bg-white rounded-lg shadow-sm border p-6">
                        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Details</h3>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Invoice Date:</span>
                                <span className="font-medium">{formatDate(invoice.created_at)}</span>
                            </div>
                            {invoice.due_date && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Due Date:</span>
                                    <span className="font-medium">{formatDate(invoice.due_date)}</span>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <span className="text-gray-600">Currency:</span>
                                <span className="font-medium">{invoice.currency}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Items */}
                <div className="bg-white rounded-lg shadow-sm border mb-6 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {invoice.items.map((item, index) => (
                                <tr key={index}>
                                    <td className="px-6 py-4 text-sm text-gray-900">{item.description}</td>
                                    <td className="px-6 py-4 text-sm text-gray-600 text-right">{item.quantity}</td>
                                    <td className="px-6 py-4 text-sm text-gray-600 text-right">{formatCurrency(item.price, invoice.currency)}</td>
                                    <td className="px-6 py-4 text-sm text-gray-900 font-medium text-right">{formatCurrency(item.total, invoice.currency)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <div className="border-t px-6 py-4">
                        <div className="flex justify-end">
                            <div className="w-64 space-y-2 text-sm">
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
                                <div className="flex justify-between border-t pt-2 text-base font-bold">
                                    <span>Total:</span>
                                    <span>{formatCurrency(invoice.total, invoice.currency)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Notes */}
                {invoice.notes && (
                    <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
                        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Notes</h3>
                        <p className="text-sm text-gray-600 whitespace-pre-line">{invoice.notes}</p>
                    </div>
                )}

                {/* Bank Details */}
                {invoice.company.bank_details && (
                    <div className="bg-blue-50 rounded-lg border border-blue-200 p-6 mb-6">
                        <h3 className="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-2">Payment Details</h3>
                        <p className="text-sm text-gray-700 whitespace-pre-line">{invoice.company.bank_details}</p>
                    </div>
                )}

                {/* Download button */}
                <div className="text-center">
                    <button
                        onClick={handleDownloadPdf}
                        className="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download PDF
                    </button>
                </div>

                {/* Footer */}
                <div className="text-center mt-8 text-xs text-gray-400">
                    <p>Powered by Invoice Generator</p>
                </div>
            </div>
        </div>
    );
}
