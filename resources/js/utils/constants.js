export const INVOICE_STATUSES = {
    draft: { label: 'Draft', color: 'gray' },
    sent: { label: 'Sent', color: 'blue' },
    paid: { label: 'Paid', color: 'green' },
    overdue: { label: 'Overdue', color: 'red' },
};

export const PRODUCT_UNITS = {
    hour: { label: 'Hour', value: 'hour' },
    piece: { label: 'Piece', value: 'piece' },
    service: { label: 'Service', value: 'service' },
};

export const PDF_TEMPLATES = [
    { value: 'classic', label: 'Classic', description: 'Clean professional layout', free: true },
    { value: 'modern', label: 'Modern', description: 'Dark header, contemporary style', free: false },
    { value: 'minimal', label: 'Minimal', description: 'Elegant minimalist design', free: false },
];

export const RECURRING_FREQUENCIES = [
    { value: 'weekly', label: 'Weekly' },
    { value: 'biweekly', label: 'Bi-weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'yearly', label: 'Yearly' },
];

export const CURRENCIES = [
    { value: 'USD', label: 'USD - US Dollar' },
    { value: 'EUR', label: 'EUR - Euro' },
    { value: 'GBP', label: 'GBP - British Pound' },
    { value: 'PLN', label: 'PLN - Polish Zloty' },
    { value: 'CHF', label: 'CHF - Swiss Franc' },
    { value: 'CZK', label: 'CZK - Czech Koruna' },
    { value: 'UAH', label: 'UAH - Ukrainian Hryvnia' },
    { value: 'RUB', label: 'RUB - Russian Ruble' },
];
