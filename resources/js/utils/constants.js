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

export const CURRENCIES = [
    { value: 'USD', label: 'USD - US Dollar' },
    { value: 'EUR', label: 'EUR - Euro' },
    { value: 'GBP', label: 'GBP - British Pound' },
    { value: 'RUB', label: 'RUB - Russian Ruble' },
    { value: 'UAH', label: 'UAH - Ukrainian Hryvnia' },
];
