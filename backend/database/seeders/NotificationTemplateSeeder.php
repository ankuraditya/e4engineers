<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            NotificationType::OrderConfirmed->value => ['Order confirmed', 'Order {{order_number}} confirmed', '<h2>Order confirmed</h2><p>Hello {{customer_name}}, your order <strong>{{order_number}}</strong> for {{order_total}} is confirmed.</p><p>Payment: {{payment_method}}</p><p><a href="{{order_url}}">View order</a></p>', ['customer_name', 'order_number', 'order_total', 'payment_method', 'order_url']],
            NotificationType::PasswordSetup->value => ['Set up account', 'Set up your E4ENGINEERS account', '<h2>Welcome to E4ENGINEERS</h2><p>Hello {{customer_name}}, set your password using this secure link. It expires according to the password reset policy.</p><p><a href="{{setup_url}}">Set your password</a></p>', ['customer_name', 'setup_url']],
            NotificationType::PaymentSuccess->value => ['Payment successful', 'Payment received for {{order_number}}', '<h2>Payment received</h2><p>We received {{amount_paid}} through {{gateway}} for {{order_number}}.</p><p>Reference: {{payment_reference}}</p><p><a href="{{order_url}}">View order</a> · <a href="{{invoice_url}}">Invoice</a></p>', ['order_number', 'amount_paid', 'gateway', 'payment_reference', 'order_url', 'invoice_url']],
            NotificationType::PaymentFailed->value => ['Payment failed', 'Payment failed for {{order_number}}', '<h2>Payment was not completed</h2><p>Your order {{order_number}} remains saved.</p><p><a href="{{retry_url}}">Retry payment</a></p>', ['order_number', 'retry_url']],
            NotificationType::InvoiceReady->value => ['Invoice ready', 'Invoice ready for {{order_number}}', '<p>Your invoice for {{order_number}} is ready.</p><p><a href="{{invoice_url}}">View secure invoice</a></p>', ['order_number', 'invoice_url']],
            NotificationType::ShipmentCreated->value => ['Shipment prepared', 'Shipment preparation started for {{order_number}}', '<p>Shipment preparation has started for {{order_number}}. Courier: {{courier_name}}.</p><p><a href="{{tracking_url}}">Track order</a></p>', ['order_number', 'courier_name', 'tracking_url']],
            NotificationType::AwbAssigned->value => ['Tracking assigned', 'Tracking available for {{order_number}}', '<p>{{courier_name}} tracking number: <strong>{{tracking_number}}</strong>.</p><p><a href="{{tracking_url}}">Track order</a></p>', ['order_number', 'courier_name', 'tracking_number', 'tracking_url']],
            NotificationType::PickupScheduled->value => ['Pickup scheduled', 'Pickup scheduled for {{order_number}}', '<p>Courier pickup is scheduled for order {{order_number}}.</p>', ['order_number']],
            NotificationType::ShipmentPickedUp->value => ['Shipment picked up', 'Order {{order_number}} has been picked up', '<p>Your order is with {{courier_name}}.</p><p><a href="{{tracking_url}}">Track order</a></p>', ['order_number', 'courier_name', 'tracking_url']],
            NotificationType::ShipmentInTransit->value => ['Shipment in transit', 'Order {{order_number}} is in transit', '<p>Your order is moving through the courier network.</p><p><a href="{{tracking_url}}">Track order</a></p>', ['order_number', 'tracking_url']],
            NotificationType::OutForDelivery->value => ['Out for delivery', 'Order {{order_number}} is out for delivery', '<p>Your order is out for delivery today.</p>', ['order_number']],
            NotificationType::Delivered->value => ['Delivered', 'Order {{order_number}} delivered', '<p>Your order {{order_number}} was marked delivered.</p>', ['order_number']],
            NotificationType::DeliveryFailed->value => ['Delivery update', 'Delivery attempt for {{order_number}} was unsuccessful', '<p>The courier could not complete delivery. Review tracking for the latest information.</p><p><a href="{{tracking_url}}">Track order</a></p>', ['order_number', 'tracking_url']],
            NotificationType::ContactEnquiryReceived->value => ['Enquiry received', 'We received enquiry {{reference_number}}', '<p>Hello {{name}}, your enquiry <strong>{{reference_number}}</strong> about {{subject}} has been received.</p>', ['name', 'reference_number', 'subject']],
            NotificationType::ContactAdminAlert->value => ['New enquiry', 'New contact enquiry {{reference_number}}', '<p>{{name}} submitted an enquiry about {{subject}}.</p>', ['name', 'reference_number', 'subject']],
            NotificationType::SupportTicketCreated->value => ['Support ticket created', 'Support ticket {{ticket_number}} created', '<p>Hello {{name}}, your ticket <strong>{{ticket_number}}</strong> has been created.</p>', ['name', 'ticket_number', 'subject']],
            NotificationType::SupportAdminAlert->value => ['Support action needed', 'Support ticket {{ticket_number}} needs attention', '<p>{{name}} submitted or replied to {{ticket_number}}: {{subject}}.</p>', ['name', 'ticket_number', 'subject']],
            NotificationType::SupportTicketReply->value => ['Support reply', 'New reply on {{ticket_number}}', '<p>Hello {{name}}, our team replied to {{ticket_number}}.</p>', ['name', 'ticket_number', 'subject']],
            NotificationType::SupportTicketResolved->value => ['Support ticket resolved', '{{ticket_number}} has been resolved', '<p>Hello {{name}}, ticket {{ticket_number}} has been resolved.</p>', ['name', 'ticket_number', 'subject']],
            NotificationType::WorkshopRegistrationReceived->value => ['Workshop registration', 'Registration received for {{workshop_title}}', '<p>Hello {{name}}, your registration for {{workshop_title}} is confirmed.</p>', ['name', 'workshop_title']],
            NotificationType::WorkshopAdminAlert->value => ['New workshop registration', 'Registration for {{workshop_title}}', '<p>{{name}} registered for {{workshop_title}}.</p>', ['name', 'workshop_title']],
            NotificationType::CareerApplicationReceived->value => ['Application received', 'Application {{reference_number}} received', '<p>Hello {{name}}, we received your application for {{job_title}}.</p>', ['name', 'job_title', 'reference_number']],
            NotificationType::CareerAdminAlert->value => ['New career application', 'Application for {{job_title}}', '<p>{{name}} applied for {{job_title}} ({{reference_number}}).</p>', ['name', 'job_title', 'reference_number']],
        ];
        foreach ($definitions as $type => [$name,$subject,$body,$variables]) {
            NotificationTemplate::firstOrCreate(['type' => $type, 'channel' => NotificationChannel::Email], ['name' => $name, 'subject' => $subject, 'body' => $body, 'available_variables' => $variables, 'is_enabled' => true]);
        }
    }
}
