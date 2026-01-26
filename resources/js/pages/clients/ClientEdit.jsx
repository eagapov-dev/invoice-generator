import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import clientsApi from '../../api/clients';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Input from '../../components/common/Input';
import Textarea from '../../components/common/Textarea';
import Alert from '../../components/common/Alert';

export default function ClientEdit() {
    const navigate = useNavigate();
    const { id } = useParams();
    const [loading, setLoading] = useState(false);
    const [fetching, setFetching] = useState(true);
    const [errors, setErrors] = useState({});
    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        company: '',
        address: '',
        notes: '',
    });

    useEffect(() => {
        loadClient();
    }, [id]);

    const loadClient = async () => {
        try {
            const response = await clientsApi.get(id);
            const client = response.data.data;
            setForm({
                name: client.name || '',
                email: client.email || '',
                phone: client.phone || '',
                company: client.company || '',
                address: client.address || '',
                notes: client.notes || '',
            });
        } catch (error) {
            console.error('Failed to load client:', error);
            navigate('/clients');
        } finally {
            setFetching(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        try {
            await clientsApi.update(id, form);
            navigate('/clients');
        } catch (err) {
            if (err.response?.status === 422) {
                setErrors(err.response.data.errors || {});
            } else {
                setErrors({ general: 'Failed to update client. Please try again.' });
            }
        } finally {
            setLoading(false);
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
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Edit Client</h1>
                <p className="text-gray-600">Update client information</p>
            </div>

            {errors.general && (
                <Alert variant="error" onClose={() => setErrors({})}>
                    {errors.general}
                </Alert>
            )}

            <Card>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <Input
                            label="Name *"
                            name="name"
                            value={form.name}
                            onChange={handleChange}
                            error={errors.name?.[0]}
                            required
                        />
                        <Input
                            label="Email"
                            name="email"
                            type="email"
                            value={form.email}
                            onChange={handleChange}
                            error={errors.email?.[0]}
                        />
                        <Input
                            label="Phone"
                            name="phone"
                            value={form.phone}
                            onChange={handleChange}
                            error={errors.phone?.[0]}
                        />
                        <Input
                            label="Company"
                            name="company"
                            value={form.company}
                            onChange={handleChange}
                            error={errors.company?.[0]}
                        />
                    </div>
                    <Textarea
                        label="Address"
                        name="address"
                        value={form.address}
                        onChange={handleChange}
                        error={errors.address?.[0]}
                        rows={3}
                    />
                    <Textarea
                        label="Notes"
                        name="notes"
                        value={form.notes}
                        onChange={handleChange}
                        error={errors.notes?.[0]}
                        rows={3}
                    />
                    <div className="flex justify-end gap-3">
                        <Button variant="secondary" type="button" onClick={() => navigate('/clients')}>
                            Cancel
                        </Button>
                        <Button type="submit" loading={loading}>
                            Save Changes
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    );
}
