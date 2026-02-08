import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import plansApi from '../api/plans';
import billingApi from '../api/billing';
import Card from '../components/common/Card';
import Badge from '../components/common/Badge';
import Button from '../components/common/Button';
import Alert from '../components/common/Alert';

function UsageBar({ label, used, limit, unlimited }) {
    if (unlimited) {
        return (
            <div className="flex justify-between items-center py-3 border-b border-gray-100 last:border-0">
                <span className="text-sm text-gray-600">{label}</span>
                <span className="text-sm font-medium text-gray-900">{used} / Unlimited</span>
            </div>
        );
    }

    const percentage = limit > 0 ? Math.min(100, (used / limit) * 100) : 0;
    const barColor = percentage >= 90 ? 'bg-red-500' : percentage >= 70 ? 'bg-yellow-500' : 'bg-blue-500';

    return (
        <div className="py-3 border-b border-gray-100 last:border-0">
            <div className="flex justify-between items-center mb-1">
                <span className="text-sm text-gray-600">{label}</span>
                <span className="text-sm font-medium text-gray-900">{used} / {limit}</span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2">
                <div className={`${barColor} h-2 rounded-full transition-all`} style={{ width: `${percentage}%` }} />
            </div>
        </div>
    );
}

export default function Billing() {
    const [limits, setLimits] = useState(null);
    const [billingStatus, setBillingStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [portalLoading, setPortalLoading] = useState(false);
    const [searchParams] = useSearchParams();
    const checkoutSuccess = searchParams.get('checkout') === 'success';

    useEffect(() => {
        loadBillingData();
    }, []);

    const loadBillingData = async () => {
        try {
            const [limitsRes, statusRes] = await Promise.all([
                plansApi.getUserLimits(),
                billingApi.getBillingStatus(),
            ]);
            setLimits(limitsRes.data.data);
            setBillingStatus(statusRes.data.data);
        } catch (err) {
            console.error('Failed to load billing data:', err);
            setError('Failed to load billing data. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleManageSubscription = async () => {
        setPortalLoading(true);
        try {
            const response = await billingApi.getPortalUrl();
            if (response.data.portal_url) {
                window.open(response.data.portal_url, '_blank');
            }
        } catch (error) {
            const message = error.response?.data?.message || 'Failed to open subscription portal.';
            alert(message);
        } finally {
            setPortalLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    const subscription = billingStatus?.subscription;
    const plan = billingStatus?.plan;
    const isFreePlan = plan?.slug === 'free';

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Billing</h1>
                <p className="text-gray-600">Manage your subscription and plan</p>
            </div>

            {error && (
                <Alert variant="error" onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}

            {checkoutSuccess && (
                <Alert type="success">
                    Payment successful! Your plan has been upgraded. It may take a moment to reflect.
                </Alert>
            )}

            {subscription?.status === 'canceled' && subscription?.on_grace_period && (
                <div className="rounded-md bg-yellow-50 p-4">
                    <div className="flex">
                        <svg className="h-5 w-5 text-yellow-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div className="ml-3">
                            <p className="text-sm text-yellow-700">
                                Your subscription has been canceled. Your plan will downgrade to Free on{' '}
                                <span className="font-medium">
                                    {new Date(subscription.current_period_end).toLocaleDateString()}
                                </span>.
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {subscription?.status === 'past_due' && (
                <Alert type="danger">
                    Your last payment failed. Please update your payment method to avoid losing access.
                </Alert>
            )}

            {/* Current Plan & Subscription */}
            <Card title="Current Plan">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-3 mb-2">
                            <h3 className="text-xl font-bold text-gray-900">{plan?.name}</h3>
                            <Badge color={isFreePlan ? 'gray' : 'blue'}>
                                {subscription ? subscription.status.replace('_', ' ') : 'Free'}
                            </Badge>
                        </div>
                        {!isFreePlan && subscription && (
                            <div className="space-y-1 text-sm text-gray-600">
                                <p>
                                    <span className="font-medium">${plan.price_monthly}/month</span>
                                    {' '}({plan.price_yearly > 0 ? `$${plan.price_yearly}/year` : ''})
                                </p>
                                {subscription.current_period_end && (
                                    <p>
                                        Next billing date:{' '}
                                        <span className="font-medium">
                                            {new Date(subscription.current_period_end).toLocaleDateString()}
                                        </span>
                                    </p>
                                )}
                                {subscription.canceled_at && (
                                    <p className="text-yellow-600">
                                        Canceled on:{' '}
                                        <span className="font-medium">
                                            {new Date(subscription.canceled_at).toLocaleDateString()}
                                        </span>
                                    </p>
                                )}
                            </div>
                        )}
                    </div>
                    <div className="flex gap-3">
                        {subscription && subscription.status !== 'expired' && (
                            <Button
                                variant="secondary"
                                onClick={handleManageSubscription}
                                loading={portalLoading}
                            >
                                Manage Subscription
                            </Button>
                        )}
                        {isFreePlan && (
                            <Link
                                to="/pricing"
                                className="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                            >
                                Upgrade Plan
                            </Link>
                        )}
                    </div>
                </div>
            </Card>

            {/* Usage */}
            {limits && (
                <Card title="Current Usage">
                    <UsageBar
                        label="Invoices this month"
                        used={limits.invoices.used}
                        limit={limits.invoices.limit}
                        unlimited={limits.invoices.unlimited}
                    />
                    <UsageBar
                        label="Clients"
                        used={limits.clients.used}
                        limit={limits.clients.limit}
                        unlimited={limits.clients.unlimited}
                    />
                    <UsageBar
                        label="Products"
                        used={limits.products.used}
                        limit={limits.products.limit}
                        unlimited={limits.products.unlimited}
                    />
                </Card>
            )}

            {/* Change plan link */}
            {!isFreePlan && (
                <div className="text-center">
                    <Link to="/pricing" className="text-sm font-medium text-blue-600 hover:text-blue-500">
                        View all plans
                    </Link>
                </div>
            )}
        </div>
    );
}
