import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import clientsApi from '../../api/clients';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Input from '../../components/common/Input';
import DeleteConfirmModal from '../../components/common/DeleteConfirmModal';
import Alert from '../../components/common/Alert';

export default function ClientList() {
    const [clients, setClients] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [deleteModal, setDeleteModal] = useState({ open: false, client: null });
    const [deleting, setDeleting] = useState(false);
    const [error, setError] = useState(null);
    const navigate = useNavigate();

    useEffect(() => {
        loadClients();
    }, [search]);

    const loadClients = async () => {
        try {
            const response = await clientsApi.getAll({ search });
            setClients(response.data.data);
        } catch (err) {
            console.error('Failed to load clients:', err);
            setError('Failed to load clients. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async () => {
        if (!deleteModal.client) return;
        setDeleting(true);
        try {
            await clientsApi.delete(deleteModal.client.id);
            setClients(clients.filter(c => c.id !== deleteModal.client.id));
            setDeleteModal({ open: false, client: null });
        } catch (err) {
            console.error('Failed to delete client:', err);
            setError('Failed to delete client. Please try again.');
        } finally {
            setDeleting(false);
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Clients</h1>
                    <p className="text-gray-600">Manage your clients</p>
                </div>
                <Button onClick={() => navigate('/clients/create')}>
                    Add Client
                </Button>
            </div>

            {error && (
                <Alert variant="error" onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}

            <Card>
                <div className="mb-4">
                    <Input
                        type="search"
                        placeholder="Search clients..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="max-w-sm"
                    />
                </div>

                {loading ? (
                    <div className="flex justify-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                ) : clients.length === 0 ? (
                    <div className="text-center py-8 text-gray-500">
                        No clients found
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoices</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {clients.map((client) => (
                                    <tr key={client.id}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="font-medium text-gray-900">{client.name}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-500">
                                            {client.email || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-500">
                                            {client.company || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-500">
                                            {client.invoices_count || 0}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <Link
                                                to={`/clients/${client.id}/edit`}
                                                className="text-blue-600 hover:text-blue-900 mr-4"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                onClick={() => setDeleteModal({ open: true, client })}
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
                onClose={() => setDeleteModal({ open: false, client: null })}
                onConfirm={handleDelete}
                loading={deleting}
                title="Delete Client"
            >
                <p>Are you sure you want to delete <strong>{deleteModal.client?.name}</strong>? This action cannot be undone.</p>
            </DeleteConfirmModal>
        </div>
    );
}
