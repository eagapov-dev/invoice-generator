import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import plansApi from '../api/plans';
import billingApi from '../api/billing';
import { useAuth } from '../context/AuthContext';
import PublicNavbar from '../components/layout/PublicNavbar';

function CheckIcon() {
    return (
        <svg className="h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
    );
}

function XIcon() {
    return (
        <svg className="h-5 w-5 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    );
}

export default function Pricing() {
    const [plans, setPlans] = useState([]);
    const [isYearly, setIsYearly] = useState(false);
    const [loading, setLoading] = useState(true);
    const [checkoutLoading, setCheckoutLoading] = useState(null);
    const { user, isAuthenticated } = useAuth();
    const navigate = useNavigate();

    const currentPlanSlug = user?.plan?.slug || 'free';

    useEffect(() => {
        plansApi.getPlans()
            .then(res => setPlans(res.data.data))
            .catch(err => console.error('Failed to load plans:', err))
            .finally(() => setLoading(false));
    }, []);

    const handleSelectPlan = async (plan) => {
        if (!isAuthenticated) {
            navigate('/register');
            return;
        }

        if (plan.slug === 'free') {
            return;
        }

        setCheckoutLoading(plan.slug);
        try {
            const response = await billingApi.createCheckout(
                plan.slug,
                isYearly ? 'yearly' : 'monthly'
            );
            window.location.href = response.data.checkout_url;
        } catch (error) {
            const message = error.response?.data?.message || 'Failed to create checkout session.';
            alert(message);
        } finally {
            setCheckoutLoading(null);
        }
    };

    const allFeatures = [
        { key: 'invoices', label: 'Invoices per month', getValue: (p) => p.max_invoices_per_month === -1 ? 'Unlimited' : p.max_invoices_per_month },
        { key: 'clients', label: 'Clients', getValue: (p) => p.max_clients === -1 ? 'Unlimited' : p.max_clients },
        { key: 'products', label: 'Products', getValue: (p) => p.max_products === -1 ? 'Unlimited' : p.max_products },
        { key: 'custom_logo', label: 'Custom logo on invoices', getValue: (p) => p.features.custom_logo },
        { key: 'custom_templates', label: 'Premium PDF templates', getValue: (p) => p.features.custom_templates },
        { key: 'remove_watermark', label: 'No watermark', getValue: (p) => p.features.remove_watermark },
        { key: 'export_csv', label: 'CSV/Excel export', getValue: (p) => p.features.export_csv },
        { key: 'recurring_invoices', label: 'Recurring invoices', getValue: (p) => p.features.recurring_invoices },
    ];

    if (loading) {
        return (
            <div className="min-h-screen bg-white">
                <PublicNavbar />
                <div className="flex items-center justify-center h-64">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-white">
            <PublicNavbar />
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
            <div className="text-center">
                <h1 className="text-3xl font-bold text-gray-900">Choose Your Plan</h1>
                <p className="mt-2 text-gray-600">Start free, upgrade when you need more</p>
            </div>

            {/* Billing toggle */}
            <div className="flex items-center justify-center gap-3">
                <span className={`text-sm font-medium ${!isYearly ? 'text-gray-900' : 'text-gray-500'}`}>Monthly</span>
                <button
                    onClick={() => setIsYearly(!isYearly)}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${isYearly ? 'bg-blue-600' : 'bg-gray-200'}`}
                >
                    <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${isYearly ? 'translate-x-6' : 'translate-x-1'}`} />
                </button>
                <span className={`text-sm font-medium ${isYearly ? 'text-gray-900' : 'text-gray-500'}`}>
                    Yearly
                    <span className="ml-1 text-green-600 text-xs font-semibold">Save 20%</span>
                </span>
            </div>

            {/* Plan cards */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-3 max-w-5xl mx-auto">
                {plans.map((plan) => {
                    const isCurrent = currentPlanSlug === plan.slug;
                    const isPopular = plan.slug === 'pro';
                    const price = isYearly ? plan.price_yearly : plan.price_monthly;
                    const period = isYearly ? '/year' : '/month';

                    return (
                        <div
                            key={plan.id}
                            className={`relative rounded-xl border-2 p-6 flex flex-col ${
                                isPopular ? 'border-blue-500 shadow-xl' : 'border-gray-200'
                            }`}
                        >
                            {isPopular && (
                                <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                                    <span className="inline-flex items-center rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">
                                        Most Popular
                                    </span>
                                </div>
                            )}

                            <div className="text-center mb-6">
                                <h3 className="text-xl font-bold text-gray-900">{plan.name}</h3>
                                <div className="mt-3">
                                    {price === 0 ? (
                                        <span className="text-4xl font-bold text-gray-900">Free</span>
                                    ) : (
                                        <>
                                            <span className="text-4xl font-bold text-gray-900">${price}</span>
                                            <span className="text-gray-500">{period}</span>
                                        </>
                                    )}
                                </div>
                                {isYearly && plan.price_monthly > 0 && (
                                    <p className="text-sm text-gray-500 mt-1">
                                        <span className="line-through">${plan.price_monthly * 12}</span>{' '}
                                        ${plan.price_yearly}/year
                                    </p>
                                )}
                            </div>

                            <ul className="space-y-3 mb-8 flex-1">
                                {allFeatures.map(({ key, label, getValue }) => {
                                    const value = getValue(plan);
                                    const isBoolean = typeof value === 'boolean';
                                    return (
                                        <li key={key} className="flex items-center gap-2 text-sm">
                                            {isBoolean ? (
                                                value ? <CheckIcon /> : <XIcon />
                                            ) : (
                                                <CheckIcon />
                                            )}
                                            <span className={isBoolean && !value ? 'text-gray-400' : 'text-gray-700'}>
                                                {isBoolean ? label : `${value} ${label.toLowerCase()}`}
                                            </span>
                                        </li>
                                    );
                                })}
                            </ul>

                            {isCurrent ? (
                                <div className="text-center py-2">
                                    <span className="inline-flex items-center rounded-full bg-green-100 px-4 py-2 text-sm font-medium text-green-800">
                                        Current Plan
                                    </span>
                                </div>
                            ) : (
                                <button
                                    onClick={() => handleSelectPlan(plan)}
                                    disabled={checkoutLoading === plan.slug}
                                    className={`w-full rounded-lg px-4 py-3 text-sm font-semibold transition-colors disabled:opacity-50 ${
                                        isPopular
                                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                                            : plan.slug === 'free'
                                                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                : 'bg-gray-900 text-white hover:bg-gray-800'
                                    }`}
                                >
                                    {checkoutLoading === plan.slug ? (
                                        <span className="flex items-center justify-center gap-2">
                                            <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                            </svg>
                                            Processing...
                                        </span>
                                    ) : plan.slug === 'free' ? (
                                        'Get Started'
                                    ) : (
                                        `Upgrade to ${plan.name}`
                                    )}
                                </button>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Feature comparison table */}
            <div className="max-w-4xl mx-auto mt-12">
                <h2 className="text-xl font-bold text-gray-900 text-center mb-6">Feature Comparison</h2>
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse">
                        <thead>
                            <tr className="border-b-2 border-gray-200">
                                <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Feature</th>
                                {plans.map(plan => (
                                    <th key={plan.id} className="text-center py-3 px-4 text-sm font-semibold text-gray-900">
                                        {plan.name}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {allFeatures.map(({ key, label, getValue }) => (
                                <tr key={key} className="border-b border-gray-100">
                                    <td className="py-3 px-4 text-sm text-gray-600">{label}</td>
                                    {plans.map(plan => {
                                        const value = getValue(plan);
                                        return (
                                            <td key={plan.id} className="text-center py-3 px-4 text-sm">
                                                {typeof value === 'boolean' ? (
                                                    value ? (
                                                        <span className="text-green-500 inline-flex justify-center"><CheckIcon /></span>
                                                    ) : (
                                                        <span className="text-gray-300 inline-flex justify-center"><XIcon /></span>
                                                    )
                                                ) : (
                                                    <span className="font-medium text-gray-900">{value}</span>
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    );
}
