import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import productsApi from '../../api/products';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Textarea from '../../components/common/Textarea';
import Alert from '../../components/common/Alert';
import { PRODUCT_UNITS } from '../../utils/constants';

export default function ProductCreate() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [form, setForm] = useState({
        name: '',
        description: '',
        price: '',
        unit: 'piece',
    });

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
            await productsApi.create({
                ...form,
                price: parseFloat(form.price) || 0,
            });
            navigate('/products');
        } catch (err) {
            if (err.response?.status === 422) {
                setErrors(err.response.data.errors || {});
            } else {
                setErrors({ general: 'Failed to create product. Please try again.' });
            }
        } finally {
            setLoading(false);
        }
    };

    const unitOptions = Object.values(PRODUCT_UNITS);

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Add Product</h1>
                <p className="text-gray-600">Create a new product or service</p>
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
                            label="Price *"
                            name="price"
                            type="number"
                            step="0.01"
                            min="0"
                            value={form.price}
                            onChange={handleChange}
                            error={errors.price?.[0]}
                            required
                        />
                        <Select
                            label="Unit *"
                            name="unit"
                            value={form.unit}
                            onChange={handleChange}
                            error={errors.unit?.[0]}
                            options={unitOptions}
                            required
                        />
                    </div>
                    <Textarea
                        label="Description"
                        name="description"
                        value={form.description}
                        onChange={handleChange}
                        error={errors.description?.[0]}
                        rows={3}
                    />
                    <div className="flex justify-end gap-3">
                        <Button variant="secondary" type="button" onClick={() => navigate('/products')}>
                            Cancel
                        </Button>
                        <Button type="submit" loading={loading}>
                            Create Product
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    );
}
