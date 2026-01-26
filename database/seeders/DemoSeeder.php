<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\ProductUnit;
use App\Models\Client;
use App\Models\CompanySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create company settings
        CompanySettings::create([
            'user_id' => $user->id,
            'company_name' => 'Acme Web Solutions',
            'address' => '123 Business Street, Suite 100, San Francisco, CA 94102',
            'phone' => '+1 (555) 123-4567',
            'email' => 'billing@acmewebsolutions.com',
            'bank_details' => "Bank: First National Bank\nAccount: 1234567890\nRouting: 021000021\nSWIFT: FNBAUS33",
            'default_currency' => 'USD',
            'default_tax_percent' => 10.00,
        ]);

        // Create clients
        $clients = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@techcorp.com',
                'phone' => '+1 (555) 234-5678',
                'company' => 'TechCorp Industries',
                'address' => '456 Innovation Ave, Austin, TX 78701',
                'notes' => 'Preferred client, NET 30 terms',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@startupventures.io',
                'phone' => '+1 (555) 345-6789',
                'company' => 'Startup Ventures',
                'address' => '789 Entrepreneur Blvd, Seattle, WA 98101',
                'notes' => 'Startup discount applied',
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'mchen@globalretail.com',
                'phone' => '+1 (555) 456-7890',
                'company' => 'Global Retail Co.',
                'address' => '321 Commerce St, New York, NY 10001',
                'notes' => 'Large enterprise client',
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily@creativestudio.design',
                'phone' => '+1 (555) 567-8901',
                'company' => 'Creative Studio Design',
                'address' => '654 Art District, Los Angeles, CA 90012',
                'notes' => null,
            ],
            [
                'name' => 'Robert Wilson',
                'email' => 'rwilson@lawfirm.legal',
                'phone' => '+1 (555) 678-9012',
                'company' => 'Wilson & Associates Law',
                'address' => '987 Legal Plaza, Chicago, IL 60601',
                'notes' => 'Requires detailed invoices',
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa@healthplus.med',
                'phone' => '+1 (555) 789-0123',
                'company' => 'HealthPlus Medical',
                'address' => '147 Medical Center Dr, Boston, MA 02108',
                'notes' => 'Healthcare industry compliance required',
            ],
        ];

        $createdClients = [];
        foreach ($clients as $clientData) {
            $createdClients[] = Client::create([
                'user_id' => $user->id,
                ...$clientData,
            ]);
        }

        // Create products/services
        $products = [
            [
                'name' => 'Web Development',
                'description' => 'Custom web application development using modern technologies',
                'price' => 150.00,
                'unit' => ProductUnit::Hour,
            ],
            [
                'name' => 'UI/UX Design',
                'description' => 'User interface and experience design services',
                'price' => 120.00,
                'unit' => ProductUnit::Hour,
            ],
            [
                'name' => 'API Integration',
                'description' => 'Third-party API integration and development',
                'price' => 175.00,
                'unit' => ProductUnit::Hour,
            ],
            [
                'name' => 'Database Design',
                'description' => 'Database architecture and optimization',
                'price' => 160.00,
                'unit' => ProductUnit::Hour,
            ],
            [
                'name' => 'Code Review',
                'description' => 'Comprehensive code review and recommendations',
                'price' => 100.00,
                'unit' => ProductUnit::Hour,
            ],
            [
                'name' => 'Technical Consultation',
                'description' => 'Expert technical advice and strategy planning',
                'price' => 200.00,
                'unit' => ProductUnit::Hour,
            ],
            [
                'name' => 'Website Hosting (Monthly)',
                'description' => 'Managed hosting with SSL, backups, and monitoring',
                'price' => 49.99,
                'unit' => ProductUnit::Service,
            ],
            [
                'name' => 'SEO Optimization Package',
                'description' => 'Complete SEO audit and optimization',
                'price' => 599.00,
                'unit' => ProductUnit::Service,
            ],
            [
                'name' => 'Logo Design',
                'description' => 'Professional logo design with revisions',
                'price' => 350.00,
                'unit' => ProductUnit::Piece,
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Cross-platform mobile application development',
                'price' => 185.00,
                'unit' => ProductUnit::Hour,
            ],
            [
                'name' => 'Security Audit',
                'description' => 'Comprehensive security assessment and report',
                'price' => 1200.00,
                'unit' => ProductUnit::Service,
            ],
            [
                'name' => 'Training Session',
                'description' => 'Technical training and knowledge transfer',
                'price' => 250.00,
                'unit' => ProductUnit::Hour,
            ],
        ];

        $createdProducts = [];
        foreach ($products as $productData) {
            $createdProducts[] = Product::create([
                'user_id' => $user->id,
                ...$productData,
            ]);
        }

        // Create invoices with various statuses
        $invoicesData = [
            // Paid invoices
            [
                'client_index' => 0,
                'status' => InvoiceStatus::Paid,
                'days_ago' => 45,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 0,
                'items' => [
                    ['product_index' => 0, 'quantity' => 40, 'description' => 'E-commerce website development'],
                    ['product_index' => 1, 'quantity' => 20, 'description' => 'UI/UX design for e-commerce'],
                    ['product_index' => 3, 'quantity' => 8, 'description' => 'Database schema design'],
                ],
            ],
            [
                'client_index' => 1,
                'status' => InvoiceStatus::Paid,
                'days_ago' => 30,
                'due_days' => 15,
                'tax_percent' => 10,
                'discount' => 200,
                'items' => [
                    ['product_index' => 8, 'quantity' => 1, 'description' => 'Company logo design'],
                    ['product_index' => 7, 'quantity' => 1, 'description' => 'Initial SEO setup'],
                ],
            ],
            [
                'client_index' => 2,
                'status' => InvoiceStatus::Paid,
                'days_ago' => 60,
                'due_days' => 30,
                'tax_percent' => 8,
                'discount' => 500,
                'items' => [
                    ['product_index' => 0, 'quantity' => 80, 'description' => 'Enterprise portal development'],
                    ['product_index' => 2, 'quantity' => 24, 'description' => 'ERP system integration'],
                    ['product_index' => 10, 'quantity' => 1, 'description' => 'Security assessment'],
                ],
            ],
            // Sent invoices (awaiting payment)
            [
                'client_index' => 0,
                'status' => InvoiceStatus::Sent,
                'days_ago' => 10,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 0,
                'items' => [
                    ['product_index' => 9, 'quantity' => 60, 'description' => 'Mobile app development - Phase 1'],
                    ['product_index' => 1, 'quantity' => 15, 'description' => 'Mobile app UI design'],
                ],
            ],
            [
                'client_index' => 3,
                'status' => InvoiceStatus::Sent,
                'days_ago' => 5,
                'due_days' => 15,
                'tax_percent' => 10,
                'discount' => 0,
                'items' => [
                    ['product_index' => 0, 'quantity' => 24, 'description' => 'Portfolio website redesign'],
                    ['product_index' => 6, 'quantity' => 3, 'description' => 'Hosting - 3 months prepaid'],
                ],
            ],
            // Overdue invoices
            [
                'client_index' => 4,
                'status' => InvoiceStatus::Overdue,
                'days_ago' => 45,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 0,
                'items' => [
                    ['product_index' => 5, 'quantity' => 8, 'description' => 'Legal tech consultation'],
                    ['product_index' => 4, 'quantity' => 4, 'description' => 'Existing system code review'],
                ],
            ],
            [
                'client_index' => 5,
                'status' => InvoiceStatus::Overdue,
                'days_ago' => 60,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 100,
                'items' => [
                    ['product_index' => 0, 'quantity' => 32, 'description' => 'Patient portal development'],
                    ['product_index' => 10, 'quantity' => 1, 'description' => 'HIPAA security audit'],
                ],
            ],
            // Draft invoices
            [
                'client_index' => 1,
                'status' => InvoiceStatus::Draft,
                'days_ago' => 2,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 0,
                'items' => [
                    ['product_index' => 9, 'quantity' => 40, 'description' => 'Mobile app development - Phase 2'],
                ],
            ],
            [
                'client_index' => 2,
                'status' => InvoiceStatus::Draft,
                'days_ago' => 1,
                'due_days' => 30,
                'tax_percent' => 8,
                'discount' => 0,
                'items' => [
                    ['product_index' => 11, 'quantity' => 16, 'description' => 'Staff training sessions'],
                    ['product_index' => 5, 'quantity' => 4, 'description' => 'Follow-up consultation'],
                ],
            ],
            // More paid invoices for better stats
            [
                'client_index' => 3,
                'status' => InvoiceStatus::Paid,
                'days_ago' => 90,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 0,
                'items' => [
                    ['product_index' => 8, 'quantity' => 1, 'description' => 'Brand identity design'],
                    ['product_index' => 0, 'quantity' => 16, 'description' => 'Initial website build'],
                ],
            ],
            [
                'client_index' => 4,
                'status' => InvoiceStatus::Paid,
                'days_ago' => 75,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 150,
                'items' => [
                    ['product_index' => 0, 'quantity' => 20, 'description' => 'Document management system'],
                    ['product_index' => 3, 'quantity' => 6, 'description' => 'Database optimization'],
                ],
            ],
            [
                'client_index' => 5,
                'status' => InvoiceStatus::Paid,
                'days_ago' => 120,
                'due_days' => 30,
                'tax_percent' => 10,
                'discount' => 0,
                'items' => [
                    ['product_index' => 5, 'quantity' => 10, 'description' => 'Healthcare IT consultation'],
                ],
            ],
        ];

        $invoiceNumber = 1;
        foreach ($invoicesData as $invoiceData) {
            $client = $createdClients[$invoiceData['client_index']];
            $createdAt = now()->subDays($invoiceData['days_ago']);
            $dueDate = $createdAt->copy()->addDays($invoiceData['due_days']);

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'invoice_number' => 'INV-'.str_pad($invoiceNumber++, 4, '0', STR_PAD_LEFT),
                'status' => $invoiceData['status'],
                'tax_percent' => $invoiceData['tax_percent'],
                'discount' => $invoiceData['discount'],
                'due_date' => $dueDate,
                'notes' => 'Thank you for your business!',
                'subtotal' => 0,
                'total' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create invoice items
            foreach ($invoiceData['items'] as $itemData) {
                $product = $createdProducts[$itemData['product_index']];

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'price' => $product->price,
                    'total' => $product->price * $itemData['quantity'],
                ]);
            }

            // Recalculate invoice totals
            $invoice->calculateTotals();
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Login with: demo@example.com / password');
    }
}
