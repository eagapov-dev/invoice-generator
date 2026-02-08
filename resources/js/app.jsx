import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/layout/ProtectedRoute';
import Layout from './components/layout/Layout';
import ErrorBoundary from './components/common/ErrorBoundary';

// Auth pages
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import ForgotPassword from './pages/auth/ForgotPassword';

// App pages
import Dashboard from './pages/Dashboard';
import ClientList from './pages/clients/ClientList';
import ClientCreate from './pages/clients/ClientCreate';
import ClientEdit from './pages/clients/ClientEdit';
import ProductList from './pages/products/ProductList';
import ProductCreate from './pages/products/ProductCreate';
import ProductEdit from './pages/products/ProductEdit';
import InvoiceList from './pages/invoices/InvoiceList';
import InvoiceCreate from './pages/invoices/InvoiceCreate';
import InvoiceEdit from './pages/invoices/InvoiceEdit';
import InvoiceView from './pages/invoices/InvoiceView';
import Settings from './pages/Settings';
import Billing from './pages/Billing';
import Pricing from './pages/Pricing';
import Landing from './pages/Landing';
import NotFound from './pages/NotFound';
import RecurringInvoiceList from './pages/recurring/RecurringInvoiceList';
import RecurringInvoiceCreate from './pages/recurring/RecurringInvoiceCreate';
import RecurringInvoiceEdit from './pages/recurring/RecurringInvoiceEdit';
import PublicInvoicePage from './pages/invoices/PublicInvoicePage';

import '../css/app.css';

function App() {
    return (
        <ErrorBoundary>
            <AuthProvider>
                <BrowserRouter>
                    <Routes>
                        {/* Public routes */}
                        <Route path="/login" element={<Login />} />
                        <Route path="/register" element={<Register />} />
                        <Route path="/forgot-password" element={<ForgotPassword />} />

                        {/* Public routes */}
                        <Route path="/pricing" element={<Pricing />} />
                        <Route path="/p/:token" element={<PublicInvoicePage />} />

                        {/* Protected routes */}
                        <Route element={<ProtectedRoute><Layout /></ProtectedRoute>}>
                            <Route path="/dashboard" element={<Dashboard />} />

                            <Route path="/clients" element={<ClientList />} />
                            <Route path="/clients/create" element={<ClientCreate />} />
                            <Route path="/clients/:id/edit" element={<ClientEdit />} />

                            <Route path="/products" element={<ProductList />} />
                            <Route path="/products/create" element={<ProductCreate />} />
                            <Route path="/products/:id/edit" element={<ProductEdit />} />

                            <Route path="/invoices" element={<InvoiceList />} />
                            <Route path="/invoices/create" element={<InvoiceCreate />} />
                            <Route path="/invoices/:id" element={<InvoiceView />} />
                            <Route path="/invoices/:id/edit" element={<InvoiceEdit />} />

                            <Route path="/recurring" element={<RecurringInvoiceList />} />
                            <Route path="/recurring/create" element={<RecurringInvoiceCreate />} />
                            <Route path="/recurring/:id/edit" element={<RecurringInvoiceEdit />} />

                            <Route path="/settings" element={<Settings />} />
                            <Route path="/billing" element={<Billing />} />
                        </Route>

                        {/* Landing page */}
                        <Route path="/" element={<Landing />} />

                        {/* 404 */}
                        <Route path="*" element={<NotFound />} />
                    </Routes>
                </BrowserRouter>
            </AuthProvider>
        </ErrorBoundary>
    );
}

const container = document.getElementById('app');
const root = createRoot(container);
root.render(<App />);
