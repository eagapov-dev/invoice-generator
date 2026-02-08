import React from 'react';
import { Link } from 'react-router-dom';
import PublicNavbar from '../components/layout/PublicNavbar';

function FeatureCard({ icon, title, description }) {
    return (
        <div className="text-center p-6">
            <div className="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100 text-blue-600 mb-4">
                {icon}
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
            <p className="text-gray-600 text-sm leading-relaxed">{description}</p>
        </div>
    );
}

function StepCard({ number, title, description }) {
    return (
        <div className="text-center">
            <div className="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white font-bold text-sm mb-4">
                {number}
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
            <p className="text-gray-600 text-sm leading-relaxed">{description}</p>
        </div>
    );
}

export default function Landing() {
    return (
        <div className="min-h-screen bg-white">
            <PublicNavbar />

            {/* Hero */}
            <section className="relative overflow-hidden">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
                    <div className="text-center max-w-3xl mx-auto">
                        <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight">
                            Professional invoices
                            <span className="text-blue-600"> in seconds</span>
                        </h1>
                        <p className="mt-6 text-lg sm:text-xl text-gray-600 leading-relaxed">
                            Create, send, and track invoices effortlessly. Get paid faster with beautiful
                            PDF invoices and shareable links your clients will love.
                        </p>
                        <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link
                                to="/register"
                                className="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/25"
                            >
                                Start Free
                            </Link>
                            <Link
                                to="/pricing"
                                className="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                View Pricing
                            </Link>
                        </div>
                        <p className="mt-4 text-sm text-gray-500">Free plan available. No credit card required.</p>
                    </div>
                </div>
                <div className="absolute inset-x-0 top-0 -z-10 transform-gpu overflow-hidden blur-3xl">
                    <div className="mx-auto aspect-[1155/678] w-[72rem] bg-gradient-to-tr from-blue-100 to-blue-50 opacity-30" />
                </div>
            </section>

            {/* Features */}
            <section className="py-20 bg-gray-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <h2 className="text-3xl font-bold text-gray-900">Everything you need to get paid</h2>
                        <p className="mt-3 text-gray-600">Powerful features to streamline your invoicing workflow</p>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        <FeatureCard
                            icon={
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            }
                            title="PDF Invoices"
                            description="Generate professional PDF invoices with multiple templates. Classic, Modern, or Minimal — choose the style that fits your brand."
                        />
                        <FeatureCard
                            icon={
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            }
                            title="Multi-Currency"
                            description="Invoice in any currency. Support for USD, EUR, GBP, PLN, CHF, and more. Per-invoice currency selection."
                        />
                        <FeatureCard
                            icon={
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                            }
                            title="Shareable Links"
                            description="Share invoices with clients via unique public links. They can view and download PDFs without needing an account."
                        />
                        <FeatureCard
                            icon={
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            }
                            title="Email Delivery"
                            description="Send invoices directly to clients via email with PDF attachment and a link to view online."
                        />
                        <FeatureCard
                            icon={
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                            }
                            title="Client Management"
                            description="Keep all your client information organized in one place. Quick access when creating invoices."
                        />
                        <FeatureCard
                            icon={
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>
                            }
                            title="Dashboard & Tracking"
                            description="Track invoice statuses, monitor payments, and get a clear overview of your business at a glance."
                        />
                    </div>
                </div>
            </section>

            {/* How It Works */}
            <section className="py-20">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <h2 className="text-3xl font-bold text-gray-900">How it works</h2>
                        <p className="mt-3 text-gray-600">Get started in three simple steps</p>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-12">
                        <StepCard
                            number="1"
                            title="Add your clients"
                            description="Enter your client details once. They'll be saved for quick access on future invoices."
                        />
                        <StepCard
                            number="2"
                            title="Create an invoice"
                            description="Add items, set your currency and tax, choose a PDF template, and preview the total."
                        />
                        <StepCard
                            number="3"
                            title="Send & get paid"
                            description="Email the invoice directly or share a public link. Download the PDF anytime."
                        />
                    </div>
                </div>
            </section>

            {/* Pricing teaser */}
            <section className="py-20 bg-gray-50">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-3xl font-bold text-gray-900">Simple, transparent pricing</h2>
                    <p className="mt-3 text-gray-600 mb-8">Start for free, upgrade as you grow</p>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div className="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 className="font-semibold text-gray-900">Free</h3>
                            <p className="text-3xl font-bold text-gray-900 mt-2">$0</p>
                            <p className="text-sm text-gray-500 mt-1">3 invoices/month</p>
                        </div>
                        <div className="bg-white rounded-xl border-2 border-blue-500 p-6 shadow-lg">
                            <h3 className="font-semibold text-blue-600">Pro</h3>
                            <p className="text-3xl font-bold text-gray-900 mt-2">$5<span className="text-base font-normal text-gray-500">/mo</span></p>
                            <p className="text-sm text-gray-500 mt-1">50 invoices/month</p>
                        </div>
                        <div className="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 className="font-semibold text-gray-900">Business</h3>
                            <p className="text-3xl font-bold text-gray-900 mt-2">$12<span className="text-base font-normal text-gray-500">/mo</span></p>
                            <p className="text-sm text-gray-500 mt-1">Unlimited invoices</p>
                        </div>
                    </div>
                    <Link
                        to="/pricing"
                        className="inline-flex items-center mt-8 text-blue-600 font-medium hover:text-blue-700 transition-colors"
                    >
                        Compare all features
                        <svg className="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </section>

            {/* CTA */}
            <section className="py-20">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-3xl font-bold text-gray-900">Ready to streamline your invoicing?</h2>
                    <p className="mt-4 text-lg text-gray-600">
                        Join thousands of freelancers and small businesses who send professional invoices with ease.
                    </p>
                    <div className="mt-8">
                        <Link
                            to="/register"
                            className="inline-flex items-center px-8 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/25"
                        >
                            Create Your First Invoice
                        </Link>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-gray-200 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div className="flex items-center gap-2 text-gray-500 text-sm">
                            <svg className="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            Invoice Generator
                        </div>
                        <div className="flex gap-6 text-sm text-gray-500">
                            <Link to="/pricing" className="hover:text-gray-700 transition-colors">Pricing</Link>
                            <Link to="/login" className="hover:text-gray-700 transition-colors">Sign In</Link>
                            <Link to="/register" className="hover:text-gray-700 transition-colors">Register</Link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
