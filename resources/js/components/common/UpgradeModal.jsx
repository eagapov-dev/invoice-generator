import React from 'react';
import { useNavigate } from 'react-router-dom';
import Modal from './Modal';
import Button from './Button';

export default function UpgradeModal({ isOpen, onClose, resource, current, limit }) {
    const navigate = useNavigate();

    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Plan Limit Reached">
            <div className="text-center py-4">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 mb-4">
                    <svg className="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <p className="text-gray-600 mb-2">
                    You've reached the limit of <span className="font-semibold">{limit} {resource}</span> on your current plan.
                </p>
                {current !== undefined && (
                    <p className="text-sm text-gray-500 mb-6">
                        Currently using: {current} / {limit}
                    </p>
                )}
                <div className="flex gap-3 justify-center">
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        onClick={() => {
                            onClose();
                            navigate('/billing');
                        }}
                    >
                        Upgrade Plan
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
