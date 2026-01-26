import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import productsApi from '../../api/products';
import Button from '../../components/common/Button';
import Card from '../../components/common/Card';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Textarea from '../../components/common/Textarea';
import Alert from '../../components/common/Alert';
import { PRODUCT_UNITS } from '../../utils/constants';

export default function ProductEdit() {
    const navigate = useNavigate();
    const { id } = useParams();
    const [loading, setLoading] = useState(false);
    const [fetching, setFetching] = useState(true);
    const [errors, setErrors] = useState({});
    const [form, setForm] = useState({
        name: '',
        description: '',
        price: '',
        unit: 'piece',
    });

    useEffect(() => {
        loadProduct();
    }, [id]);

    const loadProduct = async () => {
        try {
            const response = await productsApi.get(id);
            const product = response.data.data;
            setForm({
                name: product.name || '',
                description: product.description || '',
                price: product.price?.toString() || '',
                unit: product.unit || 'piece',
            });
        } catch (error) {
            console.error('Failed to load product:', error);
            navigate('/products');
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
            await productsApi.update(id, {
                ...form,
                price: parseFloat(form.price) || 0,
            });
            navigate('/products');
        } catch (err) {
            if (err.response?.status === 422) {
                setErrors(err.response.data.errors || {});
            } else {
                setErrors({ general: 'Failed to update product. Please try again.' });
            }
        } finally {
            setLoading(false);
        }
    };

    const unitOptions = Object.values(PRODUCT_UNITS);

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
                <h1 className="text-2xl font-bold text-gray-900">Edit Product</h1>
                <p className="text-gray-600">Update product information</p>
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
                            Save Changes
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    );
}
