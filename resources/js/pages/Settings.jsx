import React, { useState, useEffect } from 'react';
import settingsApi from '../api/settings';
import Button from '../components/common/Button';
import Card from '../components/common/Card';
import Input from '../components/common/Input';
import Select from '../components/common/Select';
import Textarea from '../components/common/Textarea';
import Alert from '../components/common/Alert';
import { CURRENCIES } from '../utils/constants';

export default function Settings() {
    const [loading, setLoading] = useState(false);
    const [fetching, setFetching] = useState(true);
    const [message, setMessage] = useState(null);
    const [errors, setErrors] = useState({});
    const [form, setForm] = useState({
        company_name: '',
        address: '',
        phone: '',
        email: '',
        bank_details: '',
        default_currency: 'USD',
        default_tax_percent: '0',
    });
    const [logo, setLogo] = useState(null);
    const [logoUrl, setLogoUrl] = useState(null);
    const [uploadingLogo, setUploadingLogo] = useState(false);

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        try {
            const response = await settingsApi.get();
            const settings = response.data.data;
            setForm({
                company_name: settings.company_name || '',
                address: settings.address || '',
                phone: settings.phone || '',
                email: settings.email || '',
                bank_details: settings.bank_details || '',
                default_currency: settings.default_currency || 'USD',
                default_tax_percent: settings.default_tax_percent?.toString() || '0',
            });
            setLogoUrl(settings.logo_url);
        } catch (err) {
            console.error('Failed to load settings:', err);
            setMessage({ type: 'error', text: 'Failed to load settings. Please try again.' });
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

    const handleLogoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setLogo(file);
        }
    };

    const handleUploadLogo = async () => {
        if (!logo) return;

        setUploadingLogo(true);
        try {
            const response = await settingsApi.uploadLogo(logo);
            setLogoUrl(response.data.data.logo_url);
            setLogo(null);
            setMessage({ type: 'success', text: 'Logo uploaded successfully!' });
        } catch (error) {
            setMessage({ type: 'error', text: 'Failed to upload logo. Please try again.' });
        } finally {
            setUploadingLogo(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});
        setMessage(null);

        try {
            await settingsApi.update({
                ...form,
                default_tax_percent: parseFloat(form.default_tax_percent) || 0,
            });
            setMessage({ type: 'success', text: 'Settings saved successfully!' });
        } catch (err) {
            if (err.response?.status === 422) {
                setErrors(err.response.data.errors || {});
            } else {
                setMessage({ type: 'error', text: 'Failed to save settings. Please try again.' });
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
                <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
                <p className="text-gray-600">Manage your company settings</p>
            </div>

            {message && (
                <Alert variant={message.type} onClose={() => setMessage(null)}>
                    {message.text}
                </Alert>
            )}

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <form onSubmit={handleSubmit}>
                        <Card title="Company Information">
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <Input
                                        label="Company Name"
                                        name="company_name"
                                        value={form.company_name}
                                        onChange={handleChange}
                                        error={errors.company_name?.[0]}
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
                                    label="Bank Details"
                                    name="bank_details"
                                    value={form.bank_details}
                                    onChange={handleChange}
                                    error={errors.bank_details?.[0]}
                                    rows={4}
                                    placeholder="Bank name, Account number, SWIFT, etc."
                                />
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <Select
                                        label="Default Currency"
                                        name="default_currency"
                                        value={form.default_currency}
                                        onChange={handleChange}
                                        options={CURRENCIES}
                                        error={errors.default_currency?.[0]}
                                    />
                                    <Input
                                        label="Default Tax %"
                                        name="default_tax_percent"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={form.default_tax_percent}
                                        onChange={handleChange}
                                        error={errors.default_tax_percent?.[0]}
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" loading={loading}>
                                        Save Settings
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    </form>
                </div>

                <div>
                    <Card title="Company Logo">
                        <div className="space-y-4">
                            {logoUrl && (
                                <div className="border rounded-lg p-4 bg-gray-50">
                                    <img
                                        src={logoUrl}
                                        alt="Company Logo"
                                        className="max-w-full max-h-32 mx-auto"
                                    />
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Upload New Logo
                                </label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleLogoChange}
                                    className="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-medium
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100
                                    "
                                />
                                <p className="mt-1 text-sm text-gray-500">
                                    PNG, JPG up to 2MB
                                </p>
                            </div>
                            {logo && (
                                <Button
                                    onClick={handleUploadLogo}
                                    loading={uploadingLogo}
                                    className="w-full"
                                >
                                    Upload Logo
                                </Button>
                            )}
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    );
}
