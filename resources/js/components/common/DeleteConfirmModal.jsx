import React from 'react';
import Modal from './Modal';
import Button from './Button';

export default function DeleteConfirmModal({ isOpen, onClose, onConfirm, loading, title, children }) {
    return (
        <Modal isOpen={isOpen} onClose={onClose} title={title}>
            <div className="text-gray-600 mb-4">{children}</div>
            <div className="flex justify-end gap-3">
                <Button variant="secondary" onClick={onClose}>
                    Cancel
                </Button>
                <Button variant="danger" onClick={onConfirm} loading={loading}>
                    Delete
                </Button>
            </div>
        </Modal>
    );
}
