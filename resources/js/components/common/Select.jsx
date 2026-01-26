import React from 'react';

export default function Select({
    label,
    error,
    options = [],
    className = '',
    placeholder = 'Select an option',
    ...props
}) {
    return (
        <div className={className}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-1">
                    {label}
                </label>
            )}
            <select
                className={`
                    block w-full rounded-md border-gray-300 shadow-sm
                    focus:border-blue-500 focus:ring-blue-500 sm:text-sm
                    ${error ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}
                `}
                {...props}
            >
                <option value="">{placeholder}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error && (
                <p className="mt-1 text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}
