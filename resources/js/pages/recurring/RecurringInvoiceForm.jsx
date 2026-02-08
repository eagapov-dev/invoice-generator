import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import clientsApi from '../../api/clients';
import productsApi from '../../api/products';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Textarea from '../../components/common/Textarea';
import Alert from '../../components/common/Alert';
import { formatCurrency } from '../../utils/formatCurrency';
import { CURRENCIES, RECURRING_FREQUENCIES } from '../../utils/constants';
import settingsApi from '../../api/settings';

export default function RecurringInvoiceForm({
    mode = 'create',
    initialForm = null,
    initialItems = null,
    onSubmit,
    title,
    subtitle,
    submitLabel,
    cancelPath = '/recurring',
    loadSettingsCurrency = false,
}) {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [clients, setClients] = useState([]);
    const [products, setProducts] = useState([]);
    const [form, setForm] = useState(initialForm || {
        client_id: '',
        frequency: 'monthly',
        ...(mode === 'create' ? { start_date: new Date().toISOString().slice(0, 10) } : {}),
        end_date: '',
        currency: 'USD',
        tax_percent: '0',
        discount: '0',
        notes: '',
    });
    const [items, setItems] = useState(initialItems || [
        { product_id: '', description: '', quantity: '1', price: '0' }
    ]);

    useEffect(() => {
        loadData();
    }, []);

    useEffect(() => {
        if (initialForm) setForm(initialForm);
    }, [initialForm]);

    useEffect(() => {
        if (initialItems) setItems(initialItems);
    }, [initialItems]);

    const loadData = async () => {
        try {
            const promises = [
                clientsApi.getAll({ per_page: 100 }),
                productsApi.getAll({ per_page: 100 }),
            ];
            if (loadSettingsCurrency) {
                promises.push(settingsApi.get());
            }

            const results = await Promise.all(promises);
            setClients(results[0].data.data);
            setProducts(results[1].data.data);

            if (loadSettingsCurrency && results[2]) {
                const defaultCurrency = results[2].data.data?.default_currency || 'USD';
                setForm(prev => ({ ...prev, currency: defaultCurrency }));
            }
        } catch (err) {
            console.error('Failed to load data:', err);
            setErrors({ general: 'Failed to load form data. Please try again.' });
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;

        if (field === 'product_id' && value) {
            const product = products.find(p => p.id.toString() === value);
            if (product) {
                newItems[index].description = product.name;
                newItems[index].price = product.price.toString();
            }
        }

        setItems(newItems);
    };

    const addItem = () => {
        setItems([...items, { product_id: '', description: '', quantity: '1', price: '0' }]);
    };

    const removeItem = (index) => {
        if (items.length > 1) {
            setItems(items.filter((_, i) => i !== index));
        }
    };

    const calculateSubtotal = () => {
        return items.reduce((sum, item) => {
            return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.price) || 0);
        }, 0);
    };

    const calculateTotal = () => {
        const subtotal = calculateSubtotal();
        const taxAmount = subtotal * (parseFloat(form.tax_percent) || 0) / 100;
        const discount = parseFloat(form.discount) || 0;
        return subtotal + taxAmount - discount;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        try {
            const data = {
                ...form,
                client_id: parseInt(form.client_id),
                tax_percent: parseFloat(form.tax_percent) || 0,
                discount: parseFloat(form.discount) || 0,
                end_date: form.end_date || null,
                items: items.map(item => ({
                    ...(item.id ? { id: item.id } : {}),
                    product_id: item.product_id ? parseInt(item.product_id) : null,
                    description: item.description,
                    quantity: parseFloat(item.quantity) || 1,
                    price: parseFloat(item.price) || 0,
                })),
            };

            await onSubmit(data);
            navigate(cancelPath);
        } catch (err) {
            if (err.response?.status === 422) {
                setErrors(err.response.data.errors || {});
            } else if (err.response?.status === 403) {
                setErrors({ general: err.response.data.message || 'Recurring invoices are not available on your plan.' });
            } else {
                setErrors({ general: `Failed to ${mode === 'create' ? 'create' : 'update'} recurring invoice. Please try again.` });
            }
        } finally {
            setLoading(false);
        }
    };

    const clientOptions = clients.map(c => ({ value: c.id.toString(), label: c.name }));
    const productOptions = [
        { value: '', label: 'Select product (optional)' },
        ...products.map(p => ({ value: p.id.toString(), label: `${p.name} - ${formatCurrency(p.price)}` }))
    ];
    const frequencyOptions = RECURRING_FREQUENCIES.map(f => ({ value: f.value, label: f.label }));

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                <p className="text-gray-600">{subtitle}</p>
            </div>

            {errors.general && (
                <Alert variant="error" onClose={() => setErrors({})}>
                    {errors.general}
                </Alert>
            )}

            <form onSubmit={handleSubmit}>
                <div className="space-y-6">
                    <Card title="Schedule">
                        <div className={`grid grid-cols-1 gap-6 sm:grid-cols-2 ${mode === 'create' ? 'lg:grid-cols-4' : 'lg:grid-cols-3'}`}>
                            <Select
                                label="Client *"
                                name="client_id"
                                value={form.client_id}
                                onChange={handleChange}
                                options={clientOptions}
                                error={errors.client_id?.[0]}
                                placeholder="Select a client"
                                required
                            />
                            <Select
                                label="Frequency *"
                                name="frequency"
                                value={form.frequency}
                                onChange={handleChange}
                                options={frequencyOptions}
                                error={errors.frequency?.[0]}
                                required
                            />
                            {mode === 'create' && (
                                <Input
                                    label="Start Date *"
                                    name="start_date"
                                    type="date"
                                    value={form.start_date}
                                    onChange={handleChange}
                                    error={errors.start_date?.[0]}
                                    required
                                />
                            )}
                            <Input
                                label="End Date (optional)"
                                name="end_date"
                                type="date"
                                value={form.end_date}
                                onChange={handleChange}
                                error={errors.end_date?.[0]}
                            />
                        </div>
                    </Card>

                    <Card title="Invoice Details">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <Select
                                label="Currency"
                                name="currency"
                                value={form.currency}
                                onChange={handleChange}
                                options={CURRENCIES.map(c => ({ value: c.value, label: c.label }))}
                            />
                        </div>
                    </Card>

                    <Card title="Items">
                        <div className="space-y-4">
                            {items.map((item, index) => (
                                <div key={index} className="flex gap-4 items-start p-4 bg-gray-50 rounded-lg">
                                    <div className="flex-1 grid grid-cols-1 gap-4 sm:grid-cols-5">
                                        <Select
                                            label="Product"
                                            value={item.product_id}
                                            onChange={(e) => handleItemChange(index, 'product_id', e.target.value)}
                                            options={productOptions}
                                            className="sm:col-span-1"
                                        />
                                        <Input
                                            label="Description *"
                                            value={item.description}
                                            onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                                            error={errors[`items.${index}.description`]?.[0]}
                                            className="sm:col-span-2"
                                            required
                                        />
                                        <Input
                                            label="Qty *"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value={item.quantity}
                                            onChange={(e) => handleItemChange(index, 'quantity', e.target.value)}
                                            error={errors[`items.${index}.quantity`]?.[0]}
                                            required
                                        />
                                        <Input
                                            label="Price *"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.price}
                                            onChange={(e) => handleItemChange(index, 'price', e.target.value)}
                                            error={errors[`items.${index}.price`]?.[0]}
                                            required
                                        />
                                    </div>
                                    <div className="pt-7">
                                        <button
                                            type="button"
                                            onClick={() => removeItem(index)}
                                            className="text-red-600 hover:text-red-800"
                                            disabled={items.length === 1}
                                        >
                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            ))}
                            <Button type="button" variant="secondary" onClick={addItem}>
                                Add Item
                            </Button>
                        </div>
                    </Card>

                    <Card title="Totals & Notes">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <Input
                                label="Tax %"
                                name="tax_percent"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value={form.tax_percent}
                                onChange={handleChange}
                            />
                            <Input
                                label="Discount"
                                name="discount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.discount}
                                onChange={handleChange}
                            />
                        </div>
                        <div className="mt-4 border-t pt-4">
                            <div className="flex justify-between py-2">
                                <span className="text-gray-600">Estimated Subtotal:</span>
                                <span className="font-medium">{formatCurrency(calculateSubtotal(), form.currency)}</span>
                            </div>
                            <div className="flex justify-between py-2 border-t text-lg font-bold">
                                <span>Estimated Total:</span>
                                <span>{formatCurrency(calculateTotal(), form.currency)}</span>
                            </div>
                        </div>
                        <div className="mt-4">
                            <Textarea
                                label="Notes"
                                name="notes"
                                value={form.notes}
                                onChange={handleChange}
                                rows={3}
                                placeholder="Additional notes for generated invoices..."
                            />
                        </div>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button variant="secondary" type="button" onClick={() => navigate(cancelPath)}>
                            Cancel
                        </Button>
                        <Button type="submit" loading={loading}>
                            {submitLabel}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    );
}
