import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import dashboardApi from '../api/dashboard';
import Card from '../components/common/Card';
import Badge from '../components/common/Badge';
import { formatCurrency, formatDate } from '../utils/formatCurrency';
import { INVOICE_STATUSES } from '../utils/constants';

function StatCard({ title, value, icon, color }) {
    return (
        <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
                <div className={`flex-shrink-0 p-3 rounded-md ${color}`}>
                    {icon}
                </div>
                <div className="ml-4">
                    <p className="text-sm font-medium text-gray-500">{title}</p>
                    <p className="text-2xl font-semibold text-gray-900">{value}</p>
                </div>
            </div>
        </div>
    );
}

export default function Dashboard() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadDashboard();
    }, []);

    const loadDashboard = async () => {
        try {
            const response = await dashboardApi.getStats();
            setData(response.data);
        } catch (error) {
            console.error('Failed to load dashboard:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    const stats = data?.stats || {};
    const recentInvoices = data?.recent_invoices || [];

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p className="text-gray-600">Overview of your invoicing activity</p>
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Total Invoices"
                    value={stats.total_invoices || 0}
                    color="bg-blue-100"
                    icon={
                        <svg className="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    }
                />
                <StatCard
                    title="Paid"
                    value={formatCurrency(stats.paid_total || 0)}
                    color="bg-green-100"
                    icon={
                        <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    }
                />
                <StatCard
                    title="Unpaid"
                    value={formatCurrency(stats.unpaid_total || 0)}
                    color="bg-yellow-100"
                    icon={
                        <svg className="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    }
                />
                <StatCard
                    title="Overdue"
                    value={formatCurrency(stats.overdue_total || 0)}
                    color="bg-red-100"
                    icon={
                        <svg className="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    }
                />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card title="Recent Invoices">
                    {recentInvoices.length === 0 ? (
                        <p className="text-gray-500 text-center py-4">No invoices yet</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {recentInvoices.map((invoice) => (
                                        <tr key={invoice.id}>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <Link to={`/invoices/${invoice.id}`} className="text-blue-600 hover:text-blue-800">
                                                    {invoice.invoice_number}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {invoice.client?.name}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {formatCurrency(invoice.total)}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <Badge color={INVOICE_STATUSES[invoice.status]?.color}>
                                                    {INVOICE_STATUSES[invoice.status]?.label}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <div className="mt-4 text-center">
                        <Link to="/invoices" className="text-sm font-medium text-blue-600 hover:text-blue-500">
                            View all invoices
                        </Link>
                    </div>
                </Card>

                <Card title="Quick Stats">
                    <div className="space-y-4">
                        <div className="flex justify-between items-center py-2 border-b">
                            <span className="text-gray-600">Clients</span>
                            <span className="font-semibold">{stats.total_clients || 0}</span>
                        </div>
                        <div className="flex justify-between items-center py-2 border-b">
                            <span className="text-gray-600">Products/Services</span>
                            <span className="font-semibold">{stats.total_products || 0}</span>
                        </div>
                        <div className="flex justify-between items-center py-2 border-b">
                            <span className="text-gray-600">Draft Invoices</span>
                            <span className="font-semibold">{stats.status_counts?.draft || 0}</span>
                        </div>
                        <div className="flex justify-between items-center py-2 border-b">
                            <span className="text-gray-600">Sent Invoices</span>
                            <span className="font-semibold">{stats.status_counts?.sent || 0}</span>
                        </div>
                        <div className="flex justify-between items-center py-2">
                            <span className="text-gray-600">Paid Invoices</span>
                            <span className="font-semibold">{stats.status_counts?.paid || 0}</span>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    );
}
