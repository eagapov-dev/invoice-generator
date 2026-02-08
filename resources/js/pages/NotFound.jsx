import React from 'react';
import { Link } from 'react-router-dom';

export default function NotFound() {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
            <div className="text-center">
                <h1 className="text-6xl font-bold text-gray-300 mb-4">404</h1>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Page not found</h2>
                <p className="text-gray-600 mb-6">The page you're looking for doesn't exist or has been moved.</p>
                <Link
                    to="/dashboard"
                    className="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    Go to Dashboard
                </Link>
            </div>
        </div>
    );
}
