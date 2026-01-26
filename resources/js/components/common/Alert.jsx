import React from 'react';

const variants = {
    success: {
        bg: 'bg-green-50',
        border: 'border-green-400',
        text: 'text-green-800',
        icon: 'text-green-400',
    },
    error: {
        bg: 'bg-red-50',
        border: 'border-red-400',
        text: 'text-red-800',
        icon: 'text-red-400',
    },
    warning: {
        bg: 'bg-yellow-50',
        border: 'border-yellow-400',
        text: 'text-yellow-800',
        icon: 'text-yellow-400',
    },
    info: {
        bg: 'bg-blue-50',
        border: 'border-blue-400',
        text: 'text-blue-800',
        icon: 'text-blue-400',
    },
};

export default function Alert({ variant = 'info', children, onClose }) {
    const styles = variants[variant];

    return (
        <div className={`rounded-md ${styles.bg} p-4 border-l-4 ${styles.border}`}>
            <div className="flex">
                <div className="flex-1">
                    <p className={`text-sm ${styles.text}`}>{children}</p>
                </div>
                {onClose && (
                    <button
                        type="button"
                        className={`ml-3 inline-flex ${styles.icon} hover:opacity-75`}
                        onClick={onClose}
                    >
                        <span className="sr-only">Dismiss</span>
                        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                        </svg>
                    </button>
                )}
            </div>
        </div>
    );
}
